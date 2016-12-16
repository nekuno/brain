<?php

namespace ApiConsumer\LinkProcessor\Processor;

use ApiConsumer\LinkProcessor\PreprocessedLink;

interface BatchProcessorInterface
{
    public function addToBatch(PreprocessedLink $preprocessedLink);

    /**
     * @return boolean
     */
    public function needToRequest();
    /**
     * @return array
     */
    public function requestBatchLinks();
}