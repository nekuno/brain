<?php


namespace ApiConsumer\Event;

use Model\Token\Token;
use Symfony\Component\EventDispatcher\Event;

class OAuthTokenEvent extends Event
{
    /** @var  Token */
    private $token;

    public function __construct(Token $token)
    {
        $this->token = $token;
    }

    public function getToken()
    {
        return $this->token;
    }
}
