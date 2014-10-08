<?php

namespace Model\User;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class UserStatusModel
{
    const USER_STATUS_INCOMPLETED = 1;
    const USER_STATUS_COMPLETED = 2;

    /**
     * @var int
     */
    protected $answerCount;

    /**
     * @var int
     */
    protected $linkCount;

    public function __construct($answerCount = 0, $linkCount = 0)
    {
        $this->answerCount = $answerCount;
        $this->linkCount = $linkCount;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return 20 <= $this->answerCount || 100 <= $this->linkCount ? self::USER_STATUS_COMPLETED : self::USER_STATUS_INCOMPLETED;
    }
} 