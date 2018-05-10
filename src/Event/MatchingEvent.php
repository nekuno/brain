<?php

namespace Event;

use Symfony\Component\EventDispatcher\Event;

class MatchingEvent extends Event
{

    protected $user1;
    protected $user2;
    protected $matching;

    public function __construct($user1, $user2, $matching)
    {

        $this->user1 = $user1;
        $this->user2 = $user2;
        $this->matching = $matching;
    }

    public function getUser1()
    {

        return $this->user1;
    }

    public function getUser2()
    {

        return $this->user2;
    }

    public function getMatching()
    {

        return $this->matching;
    }

}
