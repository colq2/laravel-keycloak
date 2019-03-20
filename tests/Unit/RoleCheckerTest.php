<?php

namespace colq2\Tests\Keycloak\Unit;


use colq2\Keycloak\Roles\RoleChecker;
use colq2\Tests\Keycloak\TestCase;

class RoleCheckerTest extends TestCase
{

    /**
     * @var RoleChecker $roleChecker
     */
    protected $roleChecker;


    protected function setUp(): void
    {
        parent::setUp();

        $this->roleChecker = new RoleChecker();
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

    public function testCheckFailsOnMissingRoleForRealmAccess()
    {
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

    public function testCheckFromToken()
    {
        $token = $this->generateToken();

        $result = $this->roleChecker
            ->for(new \colq2\Keycloak\Roles\Token($token))
            ->hasRealmAccessRole(['offline_access']);

        $this->assertTrue($result);
    }

    public function testCheckFromStringToken()
    {
        $token = (string)$this->generateToken();

        $result = $this->roleChecker
            ->for(new \colq2\Keycloak\Roles\Token($token))
            ->hasRealmAccessRole(['offline_access']);

        $this->assertTrue($result);
    }
}