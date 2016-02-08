<?php

namespace Service;

use Model\Neo4j\GraphManager;

class NotificationManager
{

    /**
     * @var GraphManager
     */
    protected $graphManager;

    public function _construct(GraphManager $graphManager)
    {
        $this->graphManager = $graphManager;
    }

    /**
     * @param $from
     * @param $to
     * @return bool
     */
    public function isNotified($from, $to)
    {

        return true;
    }

    public function notify($from, $to)
    {

    }
}