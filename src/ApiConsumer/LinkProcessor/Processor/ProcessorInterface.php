<?php

namespace ApiConsumer\LinkProcessor\Processor;
use ApiConsumer\LinkProcessor\PreprocessedLink;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
interface ProcessorInterface
{
    /**
     * @param $link PreprocessedLink
     * @return array|false Returns the processed link as array or false if the processor can not process the link
     */
    public function process(PreprocessedLink $link);
} 