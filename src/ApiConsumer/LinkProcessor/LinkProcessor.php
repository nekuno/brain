<?php

namespace ApiConsumer\LinkProcessor;

use ApiConsumer\LinkProcessor\Processor\ScraperProcessor;
use ApiConsumer\LinkProcessor\Processor\SpotifyProcessor;
use ApiConsumer\LinkProcessor\Processor\YoutubeProcessor;

class LinkProcessor
{

    /**
     * @var LinkAnalyzer
     */
    protected $analyzer;

    /**
     * @var ScraperProcessor
     */
    protected $scrapperProcessor;

    /**
     * @var YoutubeProcessor
     */
    protected $youtubeProcessor;

    /**
     * @var SpotifyProcessor
     */
    protected $spotifyProcessor;


    public function __construct(LinkAnalyzer $linkAnalyzer, ScraperProcessor $scrapperProcessor, YoutubeProcessor $youtubeProcessor, SpotifyProcessor $spotifyProcessor)
    {

        $this->analyzer = $linkAnalyzer;
        $this->scrapperProcessor = $scrapperProcessor;
        $this->youtubeProcessor = $youtubeProcessor;
        $this->spotifyProcessor = $spotifyProcessor;
    }

    /**
     * @param array $link
     * @return array
     */
    public function process(array $link)
    {

        $processor = $this->scrapperProcessor;
        $processorName = $this->analyzer->getProcessor($link);

        switch ($processorName) {
            case LinkAnalyzer::YOUTUBE:
                $processor = $this->youtubeProcessor;
                break;
            case LinkAnalyzer::SPOTIFY:
                $processor = $this->spotifyProcessor;
                break;
            case LinkAnalyzer::SCRAPPER:
                $processor = $this->scrapperProcessor;
                break;
        }

        $processedLink = $processor->process($link);

        if (!$processedLink) {
            $processedLink = $this->scrapperProcessor->process($link);
        }

        return $processedLink;
    }

}
