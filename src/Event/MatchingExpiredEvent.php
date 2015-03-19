<?php


namespace Event;

use Symfony\Component\EventDispatcher\Event;

class MatchingExpiredEvent extends Event
{

    protected $user1;

    protected $user2;

    protected $type;

    public function __construct($user1, $user2, $type)
    {

        $this->user1 = $user1;
        $this->user2 = $user2;
        $this->type = $type;
    }

    public function getType()
    {

        return $this->type;
    }

    public function getUser1()
    {

        return $this->user1;
    }

        public function getUser2()
    {

        return $this->user2;
    }

}
