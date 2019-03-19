<?php

namespace colq2\Keycloak;

use colq2\Keycloak\Contracts\Gateway;
use colq2\Keycloak\Contracts\KeyFetcher;
use colq2\Keycloak\Contracts\TokenChecker;
use colq2\Keycloak\Exceptions\SignerNotFoundException;
use Illuminate\Support\Arr;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token;

class KeycloakTokenChecker implements TokenChecker
{

    /**
     * @var Gateway
     */
    private $gateway;
    /**
     * @var KeyFetcher $keyFetcher
     */
    private $keyFetcher;

    public function __construct(Gateway $gateway, KeyFetcher $keyFetcher)
    {
        $this->gateway = $gateway;
        $this->keyFetcher = $keyFetcher;
    }

    /**
     * @param $token
     * @return bool
     */
    public function checkToken($token)
    {
        // Parse token if it is not a \Lcobucci\JWT token
        if (!$token instanceof Token) {
            try {
                $token = (new Parser)->parse($token);

                $key = $this->keyFetcher->fetchKey();
                $signer = SignerFactory::create($token->getHeader('alg', 'RS256'));
                if (!$token->verify($signer, $key)) {
                    return false;
                }

                if ($token->isExpired()) {
                    return false;
                }
            } catch (\Exception $e) {
                return false;
            }
        }


        return true;
    }

    /**
     * Check token if it valid and not expired
     * Validation is done by specs of openid connect
     * @See https://openid.net/specs/openid-connect-core-1_0.html
     * Under 3.1.3.7 ID Token Validation
     *
     * @param $token
     * @return bool
     */
    public function checkIdToken($token): bool
    {
        // Todo: Decrypt token

        // Parse token if it is not a \Lcobucci\JWT token
        if (!$token instanceof Token) {
            try {
                $token = (new Parser)->parse($token);
            } catch (\Exception $e) {
                return false;
            }
        }

        $claims = [];
        foreach ($token->getClaims() as $claim) {
            $claims[$claim->getName()] = $claim->getValue();
        }
        // 2. The Issuer Identifier for the OpenID Provider (which is typically obtained during Discovery)
        // MUST exactly match the value of the iss (issuer) Claim.
        if (Arr::get($claims, 'iss') !== $this->gateway->getBaseUrlWithRealm()) {
            return false;
        }

        // 3. The Client MUST validate that the aud (audience) Claim contains its client_id value
        // registered at the Issuer identified by the iss (issuer) Claim as an audience.
        // The aud (audience) Claim MAY contain an array with more than one element.
        // The ID Token MUST be rejected if the ID Token does not list the Client as a valid audience,
        // or if it contains additional audiences not trusted by the Client.
        // In short: aud have to be the same as our client id
        // TODO: Add config to allow an array with trusted clients

        $aud = Arr::get($claims, 'aud');
        $azp = Arr::get($claims, 'azp', null);

        if (is_array($aud)) {
            // We have an audience array
            if (!in_array(config('keycloak.client_id'), $aud)) {
                return false;
            }

            // 4. If the ID Token contains multiple audiences, the Client SHOULD verify that an azp Claim is present.
            // TODO: We only support a single client at the moment
            if (!$azp) return false;

            // 5. If an azp (authorized party) Claim is present, the Client SHOULD verify that its client_id is the Claim Value.
            if ($azp !== config('keycloak.client_id')) {
                return false;
            }

        } else {
            // We have a single audience
            if (Arr::get($claims, 'aud') !== config('keycloak.client_id')) {
                return false;
            }

            // 5. If an azp (authorized party) Claim is present, the Client SHOULD verify that its client_id is the Claim Value.
            if ($azp && $azp !== config('keycloak.client_id')) {
                return false;
            }
        }


        // 6. If the ID Token is received via direct communication between the Client and the
        // Token Endpoint (which it is in this flow), the TLS server validation MAY be used to
        // validate the issuer in place of checking the token signature.
        // The Client MUST validate the signature of all other ID Tokens according to JWS [JWS] using
        // the algorithm specified in the JWT alg Header Parameter. The Client MUST use the keys provided by the Issuer.
        // Fetch public key
        $publicKey = $this->gateway->fetchPublicKey();
        $publicKey = "-----BEGIN PUBLIC KEY-----" . PHP_EOL . $publicKey . PHP_EOL . '-----END PUBLIC KEY-----' . PHP_EOL;

        // Determine signature method
        try {
            $alg = SignerFactory::create($token->getHeader('alg'));
        } catch (SignerNotFoundException $e) {
            return false;
        }

        // Verify the algorithm
        try {
            if (!$token->verify($alg, $publicKey)) {
                return false;
            }

        } catch (\Exception $e) {
            return false;
        }

        // 7. The alg value SHOULD be the default of RS256 or the algorithm sent by the Client in
        // the id_token_signed_response_alg parameter during Registration.
        // ... we are registering the clients from gui...
        // Short: Check the used algorithm
        if ($alg->getAlgorithmId() !== config('keycloak.signature_algorithm', 'RS256')) {
            return false;
        }

        // 8. If the JWT alg Header Parameter uses a MAC based algorithm such as HS256, HS384, or HS512,
        // the octets of the UTF-8 representation of the client_secret corresponding
        // to the client_id contained in the aud (audience) Claim are used as the key to validate the signature.
        // For MAC based algorithms, the behavior is unspecified if the aud is multi-valued or if an azp value is present that is different than the aud value.
        // WTF?
        // TODO: Understand and implement it
        if (in_array($alg, ['HS256', 'HS384', 'HS512'])) {

        }

        // 9. The current time MUST be before the time represented by the exp Claim.
        // Short: Is token expired?
        if ($token->isExpired()) {
            return false;
        }

        // 10. The iat Claim can be used to reject tokens that were issued too far away
        // from the current time, limiting the amount of time that nonces need
        // to be stored to prevent attacks. The acceptable range is Client specific.
        // Short: We are not using nonces at the moment

        // 11. If a nonce value was sent in the Authentication Request,
        // a nonce Claim MUST be present and its value checked to verify
        // that it is the same value as the one that was sent in the Authentication Request.
        // The Client SHOULD check the nonce value for replay attacks.
        // The precise method for detecting replay attacks is Client specific.
        // Short: We are not using nonces at the moment

        // 12. If the acr Claim was requested, the Client SHOULD check that the asserted
        // Claim Value is appropriate. The meaning and processing of acr Claim Values
        // is out of scope for this specification.
        // We are not requesting the act value

        // 13. If the auth_time Claim was requested, either through a specific request
        // for this Claim or by using the max_age parameter, the Client SHOULD check
        // the auth_time Claim value and request re-authentication if it determines
        // too much time has elapsed since the last End-User authentication.
        $authTime = Arr::get($claims, 'auth_time');
        $maxAge = config('keycloak.max_age');
        if ($authTime && $maxAge && $authTime < time() - $maxAge) {
            return false;
        }

        return true;
    }
}