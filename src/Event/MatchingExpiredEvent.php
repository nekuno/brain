<?php


namespace Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class MatchingExpiredEvent
 * @package Event
 */
class MatchingExpiredEvent extends Event
{

    /**
     * @var
     */
    protected $user1;

    /**
     * @var
     */
    protected $user2;

    /**
     * @var
     */
    protected $type;

    /**
     * @param $user1
     * @param $user2
     * @param $type
     */
    public function __construct($user1, $user2, $type)
    {

        $this->user1 = $user1;

        $this->user2 = $user2;

        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getType()
    {

        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getUser1()
    {

        return $this->user1;
    }

    /**
     * @return mixed
     */
    public function getUser2()
    {

        return $this->user2;
    }

}
