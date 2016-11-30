<?php

namespace ApiConsumer\LinkProcessor;

use ApiConsumer\Factory\ProcessorFactory;
use ApiConsumer\Images\ImageAnalyzer;
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
        $preprocessedLink->getLink()->setUrl($preprocessedLink->getCanonical());

        $scrapper = $this->processorFactory->getScrapperProcessor();
        $response = $scrapper->requestItem($preprocessedLink);

        $scrapper->hydrateLink($preprocessedLink, $response);
        $scrapper->addTags($preprocessedLink, $response);

        if (!$preprocessedLink->getLink()->getThumbnail()) {
            $image = $this->getThumbnail($scrapper, $response);
            $preprocessedLink->getLink()->setThumbnail($image);
        }

        return $preprocessedLink->getLink();
    }

    public function process(PreprocessedLink $preprocessedLink)
    {
        $preprocessedLink->getLink()->setUrl($preprocessedLink->getCanonical());

        $processor = $this->selectProcessor($preprocessedLink);

        $response = $processor->requestItem($preprocessedLink);

        $image = $this->getThumbnail($preprocessedLink, $processor, $response);
        $preprocessedLink->getLink()->setThumbnail($image);

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

    protected function getThumbnail(PreprocessedLink $preprocessedLink, ProcessorInterface $processor, array $response)
    {
        $images = $processor->getImages($preprocessedLink, $response);

        return $this->imageAnalyzer->selectImage($images);
    }
}
