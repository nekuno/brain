<?php

namespace ApiConsumer\LinkProcessor\Processor;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
interface ProcessorInterface
{
    /**
     * @param $link
     * @return array
     */
    public function process(array $link);
} 