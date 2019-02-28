<?php

namespace colq2\Tests\Keycloak\Integration;

use colq2\Tests\Keycloak\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function setUp()
    {
        parent::setUp();

        if(!config('keycloak.test_integration')){
            $this->markTestSkipped();
        }
    }
}