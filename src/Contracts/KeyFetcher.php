<?php

namespace colq2\Keycloak\Contracts;


interface KeyFetcher
{

    /**
     * Fetch and return key
     *
     * @return string
     */
    public function fetchKey(): string;

}