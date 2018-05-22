<?php

namespace ApiConsumer\LinkProcessor\Processor;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use Model\Link\Link;

interface BatchProcessorInterface
{
    /**
     * @param $batch PreprocessedLink[]
     * @return bool
     */
    public function needToRequest(array $batch);

    /**
     * @param $batch PreprocessedLink[]
     * @return Link[]
     */
    public function requestBatchLinks(array $batch);
}