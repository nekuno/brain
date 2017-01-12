<?php

namespace ApiConsumer\LinkProcessor\Processor;

use ApiConsumer\LinkProcessor\PreprocessedLink;

interface BatchProcessorInterface
{
    /**
     * @param $batch PreprocessedLink[]
     * @return bool
     */
    public function needToRequest(array $batch);

    /**
     * @param $batch PreprocessedLink[]
     * @return array
     */
    public function requestBatchLinks(array $batch);
}