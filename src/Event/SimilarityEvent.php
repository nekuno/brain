<?php

namespace Event;

use Symfony\Component\EventDispatcher\Event;

class SimilarityEvent extends Event
{

    protected $user1;
    protected $user2;
    protected $similarity;

    public function __construct($user1, $user2, $similarity)
    {

        $this->user1 = $user1;
        $this->user2 = $user2;
        $this->similarity = $similarity;
    }

    public function getUser1()
    {

        return $this->user1;
    }

    public function getUser2()
    {

        return $this->user2;
    }

    public function getSimilarity()
    {

        return $this->similarity;
    }

}
