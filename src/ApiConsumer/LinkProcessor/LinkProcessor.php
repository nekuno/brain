<?php

namespace ApiConsumer\LinkProcessor;

use ApiConsumer\Factory\ProcessorFactory;
use ApiConsumer\LinkProcessor\Processor\ProcessorInterface;

class LinkProcessor
{
    private $processorFactory;

    private $lastResponse = array();


    public function __construct(ProcessorFactory $processorFactory)
    {
        $this->processorFactory = $processorFactory;
    }

    public function scrape(PreprocessedLink $preprocessedLink)
    {
        $preprocessedLink->getLink()->setUrl($preprocessedLink->getCanonical());

        $scrapper = $this->processorFactory->getScrapperProcessor();
        $response = $scrapper->requestItem($preprocessedLink);
        $scrapper->hydrateLink($preprocessedLink, $response);
        $scrapper->addTags($preprocessedLink, $response);

        return $preprocessedLink->getLink();
    }

    public function process(PreprocessedLink $preprocessedLink)
    {
        $preprocessedLink->getLink()->setUrl($preprocessedLink->getCanonical());

        $processor = $this->selectProcessor($preprocessedLink);

        $response = $processor->requestItem($preprocessedLink);
//        $this->lastResponse = $response;

        $processor->hydrateLink($preprocessedLink, $response);
        $processor->addTags($preprocessedLink, $response);
        $processor->getSynonymousParameters($preprocessedLink, $response);


        if (!$preprocessedLink->getLink()->isComplete()) {
            $this->scrape($preprocessedLink);
        }

        return $preprocessedLink->getLink();
    }

    protected function selectProcessor(PreprocessedLink $link)
    {
        $processorName = LinkAnalyzer::getProcessorName($link);

        return $this->processorFactory->build($processorName);
    }

    public function getSynonymousParametersFromCache(PreprocessedLink $preprocessedLink)
    {
        $processor = $this->selectProcessor($preprocessedLink);
        $synonymousParameters = $processor->getSynonymousParameters($preprocessedLink, $this->lastResponse);

        return $synonymousParameters;
    }

}
