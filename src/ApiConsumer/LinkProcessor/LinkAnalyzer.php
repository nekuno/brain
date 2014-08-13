<?php

namespace ApiConsumer\LinkProcessor;

use ApiConsumer\LinkProcessor\Processor\ProcessorInterface;
use ApiConsumer\LinkProcessor\Processor\ScrapperProcessor;
use ApiConsumer\LinkProcessor\Processor\SpotifyProcessor;
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

    /**
     * @var SpotifyProcessor
     */
    protected $spotifyProcessor;

    public function __construct(ScrapperProcessor $scrapperProcessor, YoutubeProcessor $youtubeProcessor, SpotifyProcessor $spotifyProcessor)
    {
        $this->scrapperProcessor = $scrapperProcessor;
        $this->youtubeProcessor = $youtubeProcessor;
        $this->spotifyProcessor = $spotifyProcessor;
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

        if (strpos($link['url'], 'spotify.com') !== false) {
            return $this->spotifyProcessor;
        }

        return $this->scrapperProcessor;
    }
} 