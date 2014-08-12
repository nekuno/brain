<?php

namespace ApiConsumer\LinkProcessor;

class LinkProcessor
{

    /**
     * @var LinkAnalyzer
     */
    protected $analyzer;


    public function __construct(LinkAnalyzer $linkAnalyzer)
    {

        $this->analyzer = $linkAnalyzer;
    }

    /**
     * @param array $link
     * @return array
     */
    public function process(array $link)
    {

        $processor = $this->analyzer->getProcessor($link);
        $processedLink = $processor->process($link);
        return $processedLink;
    }

}
