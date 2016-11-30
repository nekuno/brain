<?php

namespace ApiConsumer\Exception;


class TokenException extends \RuntimeException
{
    protected $token;

    /**
     * Get token
     *
     * @return array
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set token
     *
     * @param array $token token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }
}