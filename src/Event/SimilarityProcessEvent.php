<?php

namespace Event;

use Symfony\Component\EventDispatcher\Event;

class SimilarityProcessEvent extends Event
{

    protected $userId;
    protected $processId;

    public function __construct($userId, $processId)
    {
        $this->userId = (integer)$userId;
        $this->processId = (integer)$processId;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getProcessId()
    {
        return $this->processId;
    }
}
