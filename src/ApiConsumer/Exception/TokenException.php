<?php

namespace ApiConsumer\Exception;


use Model\Token\Token;

class TokenException extends \RuntimeException
{
    /** @var  Token */
    protected $token;

    /**
     * @return Token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param Token $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }
}