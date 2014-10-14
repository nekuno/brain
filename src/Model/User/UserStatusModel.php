<?php

namespace Model\User;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class UserStatusModel
{
    const USER_STATUS_DISABLED = 'disabled';
    const USER_STATUS_INCOMPLETE = 'incomplete';
    const USER_STATUS_COMPLETE = 'complete';

    /**
     * @var string
     */
    protected $status;

    /**
     * @var int
     */
    protected $answerCount;

    /**
     * @var int
     */
    protected $linkCount;

    public function __construct($status = null, $answerCount = 0, $linkCount = 0)
    {
        if (in_array($status, $this->getStatuses())) {
            $this->status = $status;
        }
        $this->answerCount = $answerCount;
        $this->linkCount = $linkCount;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        if ($this->status === self::USER_STATUS_DISABLED) {
            return $this->status;
        }

        return 20 <= $this->answerCount || 100 <= $this->linkCount ? self::USER_STATUS_COMPLETE : self::USER_STATUS_INCOMPLETE;
    }

    public function getStatuses()
    {
        return array(
            self::USER_STATUS_DISABLED,
            self::USER_STATUS_INCOMPLETE,
            self::USER_STATUS_COMPLETE,
        );
    }
} 