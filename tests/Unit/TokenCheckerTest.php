<?php

namespace colq2\Tests\Keycloak\Unit;

use colq2\Keycloak\Contracts\TokenChecker;
use colq2\Keycloak\SignerFactory;
use colq2\Tests\Keycloak\Factories\JWTFactory;
use colq2\Tests\Keycloak\Factories\KeyPairFactory;
use colq2\Tests\Keycloak\TestCase;
use colq2\Tests\Keycloak\Traits\FakeGateway;
use Lcobucci\JWT\Token;

class TokenCheckerTest extends TestCase
{

    use FakeGateway;
    /**
     * Factory to generate jwt tokens
     * @var JWTFactory $tokenFactory
     */
    protected $tokenFactory;

    /**
     * @var TokenChecker $checker
     */
    protected $checker;

    /**
     * @var Token $token
     */
    private $token;

    /**
     * The data to create a token
     *
     * @var
     */
    protected $tokenData;

    /**
     * @var string
     */
    protected $signerAlgorithm = 'RS256';

    /**
     * Set up Tests
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->checker = $this->app->make(TokenChecker::class);

        // Valid Token data
        $this->tokenData = [
            'iss' => config('keycloak.base_url') . '/realms/' . config('keycloak.realm'),
            'aud' => config('keycloak.client_id'),
        ];

    }

    protected function buildToken(){
        $builder = JWTFactory::createBuilder($this->tokenData);

        $builder->sign(SignerFactory::create($this->signerAlgorithm), $this->privateKey);

        $this->token = $builder->getToken();
    }

    protected function setDataAndBuildToken($data = []){
        foreach ($data as $key => $value){
            $this->tokenData[$key] = $value;
        }

        $this->buildToken();
    }

    public function testCheckFailsOnNonce(){
        $token = 'AAAA';

        $this->assertFalse($this->checker->checkIdToken($token));
    }

    public function testCheckIsSuccessful()
    {
        $this->buildToken();

        $this->assertTrue($this->checker->checkIdToken($this->token));
    }

    public function testCheckIsSuccessfulOnSetAzp()
    {
        $this->setDataAndBuildToken(['azp' => config('keycloak.client_id')]);
    }

    public function testCheckFailsOnWrongIssuer()
    {
        $this->setDataAndBuildToken(['iss' => 'AAAA']);

        $this->assertFalse($this->checker->checkIdToken($this->token));
    }

    public function testCheckFailsOnSingleAndWrongAudience()
    {
        $this->setDataAndBuildToken(['aud' => 'AAAA']);

        $this->assertFalse($this->checker->checkIdToken($this->token));
    }

    public function testCheckIsSuccessfulOnMultipleAudience ()
    {
        // TODO: JWT Library does not support multi audiences
        $this->markTestSkipped();
        $this->setDataAndBuildToken([
            'aud' => [config('keycloak.client_id'), 'test_audience'],
            'azp' => config('keycloak.client_id')
        ]);

        $this->assertTrue($this->checker->checkIdToken($this->token));
    }

    public function testCheckFailsOnMultipleAudienceWhenNoAzpIsSet()
    {
        $this->markTestSkipped();

        $this->setDataAndBuildToken([
            'aud' => [config('keycloak.client_id'), 'test_audience'],
        ]);

        $this->assertFalse($this->checker->checkIdToken($this->token));
    }

    public function testCheckFailsOnMultipleAudienceWhenWrongAzpIsSet()
    {
        $this->markTestSkipped();

        $this->setDataAndBuildToken([
            'aud' => [config('keycloak.client_id'), 'test_audience'],
            'azp' => 'AAAA'
        ]);

        $this->assertFalse($this->checker->checkIdToken($this->token));
    }

    public function testCheckFailsWhenWrongAzpIsSet()
    {
        $this->setDataAndBuildToken([
            'azp' => 'AAAA',
        ]);

        $this->assertFalse($this->checker->checkIdToken($this->token));
    }

    public function testCheckFailsWhenTokenIsSignedWithWrongKey()
    {
        $wrongKeyPair = KeyPairFactory::create();

        $this->privateKey = $wrongKeyPair->getPrivateKey();

        $this->buildToken();

        $this->assertFalse($this->checker->checkIdToken($this->token));
    }

    public function testCheckFailsWhenServerUserAnotherSignerAlgorithm()
    {
        $this->signerAlgorithm = 'HS256';

        $this->assertFalse($this->checker->checkIdToken($this->token));
    }

    public function testCheckFailsOnExpiredToken()
    {
        $this->setDataAndBuildToken([
           'iat' => time() - 400,
           'exp' => time() - 100
        ]);

        $this->assertFalse($this->checker->checkIdToken($this->token));
    }

    // TODO Test 10 - 13

}