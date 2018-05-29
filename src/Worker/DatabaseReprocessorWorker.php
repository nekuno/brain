<?php

namespace Worker;

use Service\AMQPManager;

class DatabaseReprocessorWorker extends LinkProcessorWorker
{
    protected $queue = AMQPManager::REFETCHING;
}