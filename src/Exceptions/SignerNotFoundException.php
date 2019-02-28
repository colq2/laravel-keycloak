<?php

namespace colq2\Keycloak\Exceptions;

class SignerNotFoundException extends \Exception
{
    /**
     * Signer which couldn't be found
     *
     * @var string
     */
    protected $algorithm;

    /**
     * Create a new SignerNotFoundException.
     *
     * @param string $message
     * @param string $algorithm
     * @return void
     */
    public function __construct(string $message = "", string $algorithm = "")
    {
        parent::__construct($message);
        $this->algorithm = $algorithm;
    }

    /**
     * @return string
     */
    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    /**
     * @param string $algorithm
     * @return \colq2\Keycloak\SignerNotFoundException
     */
    public function setAlgorithm(string $algorithm): self
    {
        $this->algorithm = $algorithm;

        return $this;
    }
}