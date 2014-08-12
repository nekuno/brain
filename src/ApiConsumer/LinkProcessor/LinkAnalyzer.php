<?php

namespace ApiConsumer\LinkProcessor;

use ApiConsumer\LinkProcessor\Processor\ProcessorInterface;
use ApiConsumer\LinkProcessor\Processor\ScrapperProcessor;
use ApiConsumer\LinkProcessor\Processor\YoutubeProcessor;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class LinkAnalyzer
{

    /**
     * @var ScrapperProcessor
     */
    protected $scrapperProcessor;

    /**
     * @var YoutubeProcessor
     */
    protected $youtubeProcessor;

    public function __construct(ScrapperProcessor $scrapperProcessor, YoutubeProcessor $youtubeProcessor)
    {
        $this->scrapperProcessor = $scrapperProcessor;
        $this->youtubeProcessor = $youtubeProcessor;
    }

    /**
     * @param $link
     * @return ProcessorInterface
     */
    public function getProcessor($link)
    {
        if (strpos($link['url'], 'youtube.com') !== false) {
            return $this->youtubeProcessor;
        }

        return $this->scrapperProcessor;
    }
} 