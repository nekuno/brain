<?php

namespace ApiConsumer\Exception;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
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