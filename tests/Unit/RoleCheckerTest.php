<?php

namespace colq2\Tests\Keycloak\Unit;


use colq2\Keycloak\Roles\RoleChecker;
use colq2\Keycloak\SignerFactory;
use colq2\Tests\Keycloak\Factories\KeyPairFactory;
use colq2\Tests\Keycloak\Stubs\KeycloakUser;
use colq2\Tests\Keycloak\TestCase;
use Illuminate\Support\Arr;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Token;

class RoleCheckerTest extends TestCase
{

    /**
     * @var RoleChecker $roleChecker
     */
    protected $roleChecker;


    protected function setUp()
    {
        parent::setUp();

        $this->roleChecker = new RoleChecker();
    }

    protected function generateToken()
    {
        $roles = $this->generateRoles();

        $builder = new Builder();
        $builder->set('realm_access', Arr::get($roles, 'realm_access'));
        $builder->set('resource_access', Arr::get($roles, 'resource_access'));

        $keyPair = KeyPairFactory::create();
        $builder->sign(SignerFactory::create('RS256'), $keyPair->getPrivateKey());

        return $builder->getToken();
    }

    protected function generateUser() {
        $user = new KeycloakUser([
            'sub' => 'subject',
            'username' => 'johndoe',
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'picture' => null,
            'roles' => $this->generateRoles()
        ]);

        $user->save();

        return $user->refresh();
    }

    protected function generateRoles(){
        return [
            'realm_access' => [
                'roles' => ['offline_access', 'uma_authorization']
            ],
            'resource_access' => [
                'test-client' => [
                    'roles' => ['update-test', 'create-test', 'view-test'] // manage-test, delete-test
                ],
                'test-client2' => [
                    'roles' => ['view-profile']
                ]
            ]
        ];
    }

    public function testCheckSingleRoleForRealmAccess()
    {
        $user = $this->generateUser();

        $result = $this->roleChecker
            ->for($user)
            ->hasRealmAccessRole('offline_access');

        $this->assertTrue($result);
    }

    public function testCheckMultiRoleForRealmAccess()
    {
        $user = $this->generateUser();

        $result = $this->roleChecker
            ->for($user)
            ->hasRealmAccessRole(['offline_access', 'uma_authorization']);

        $this->assertTrue($result);
    }

    public function testCheckFailsOnMissingRoleForRealmAccess(){
        $user = $this->generateUser();

        $result = $this->roleChecker
            ->for($user)
            ->hasRealmAccessRole(['not_existing_role']);

        $this->assertFalse($result);
    }

    public function testCheckSingleRoleForResourceAccess()
    {
        $user = $this->generateUser();

        $result = $this->roleChecker
            ->for($user)
            ->hasResourceAccessRole('test-client', ['update-test']);

        $this->assertTrue($result);
    }

    public function testCheckMultiRoleForResourceAccess()
    {
        $user = $this->generateUser();

        $result = $this->roleChecker
            ->for($user)
            ->hasResourceAccessRole('test-client', ['update-test', 'create-test']);

        $this->assertTrue($result);
    }

    public function testCheckFailsOnMissingRoleForResourceAccess()
    {
        $user = $this->generateUser();

        $result = $this->roleChecker
            ->for($user)
            ->hasResourceAccessRole('test-client', ['update-test', 'create-test', 'delete-test']);

        $this->assertFalse($result);
    }

    public function testCheckFailsOnMissingClientForResourceAccess()
    {
        $user = $this->generateUser();

        $result = $this->roleChecker
            ->for($user)
            ->hasResourceAccessRole('not-existing-client', ['update-test', 'create-test', 'delete-test']);

        $this->assertFalse($result);
    }

    public function testCheckFromToken(){
        $token = $this->generateToken();

        $result = $this->roleChecker
            ->for(new \colq2\Keycloak\Roles\Token($token))
            ->hasRealmAccessRole(['offline_access']);

        $this->assertTrue($result);
    }

    public function testCheckFromStringToken(){
        $token = (string) $this->generateToken();

        $result = $this->roleChecker
            ->for(new \colq2\Keycloak\Roles\Token($token))
            ->hasRealmAccessRole(['offline_access']);

        $this->assertTrue($result);
    }
}