<?php

namespace ApiConsumer\LinkProcessor;

use ApiConsumer\Factory\ProcessorFactory;
use ApiConsumer\Images\ImageAnalyzer;
use ApiConsumer\LinkProcessor\Processor\BatchProcessorInterface;
use ApiConsumer\LinkProcessor\Processor\ProcessorInterface;

class LinkProcessor
{
    private $processorFactory;
    private $imageAnalyzer;

    public function __construct(ProcessorFactory $processorFactory, ImageAnalyzer $imageAnalyzer)
    {
        $this->processorFactory = $processorFactory;
        $this->imageAnalyzer = $imageAnalyzer;
    }

    public function scrape(PreprocessedLink $preprocessedLink)
    {
        $preprocessedLink->getFirstLink()->setUrl($preprocessedLink->getUrl());

        $scrapper = $this->processorFactory->getScrapperProcessor();
        $response = $scrapper->requestItem($preprocessedLink);

        $scrapper->hydrateLink($preprocessedLink, $response);
        $scrapper->addTags($preprocessedLink, $response);

        if (!$preprocessedLink->getFirstLink()->getThumbnail()) {
            $image = $this->getThumbnail($preprocessedLink, $scrapper, $response);
            $preprocessedLink->getFirstLink()->setThumbnail($image);
        }

        return $preprocessedLink->getLinks();
    }

    public function process(PreprocessedLink $preprocessedLink)
    {
        $preprocessedLink->getFirstLink()->setUrl($preprocessedLink->getUrl());

        $processor = $this->selectProcessor($preprocessedLink);

        if ($processor instanceof BatchProcessorInterface) {
            $processor->addToBatch($preprocessedLink);

            $links = $processor->needToRequest() ? $processor->requestBatchLinks() : array();

        } else {
            $links = $this->processSingle($preprocessedLink, $processor);
        }

        return $links;
    }

    protected function selectProcessor(PreprocessedLink $link)
    {
        $processorName = LinkAnalyzer::getProcessorName($link);

        return $this->processorFactory->build($processorName);
    }

    protected function getThumbnail(PreprocessedLink $preprocessedLink, ProcessorInterface $processor, array $response)
    {
        $images = $processor->getImages($preprocessedLink, $response);

        return $this->imageAnalyzer->selectImage($images);
    }

    protected function processSingle(PreprocessedLink $preprocessedLink, ProcessorInterface $processor)
    {
        $response = $processor->requestItem($preprocessedLink);

        $image = $this->getThumbnail($preprocessedLink, $processor, $response);
        $preprocessedLink->getFirstLink()->setThumbnail($image);

        $processor->hydrateLink($preprocessedLink, $response);
        $processor->addTags($preprocessedLink, $response);
        $processor->getSynonymousParameters($preprocessedLink, $response);

        if (!$preprocessedLink->getFirstLink()->isComplete()) {
            $this->scrape($preprocessedLink);
        }

        return $preprocessedLink->getLinks();
    }
}
