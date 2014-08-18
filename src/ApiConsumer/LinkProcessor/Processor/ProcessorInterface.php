<?php

namespace ApiConsumer\LinkProcessor\Processor;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
interface ProcessorInterface
{
    /**
     * @param $link
     * @return array|false Returns the processed link as array or false if the processer can not process the link
     */
    public function process(array $link);
} 