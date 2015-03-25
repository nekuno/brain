<?php

namespace ApiConsumer\LinkProcessor;

use ApiConsumer\LinkProcessor\Processor\ScraperProcessor;
use ApiConsumer\LinkProcessor\Processor\SpotifyProcessor;
use ApiConsumer\LinkProcessor\Processor\YoutubeProcessor;
use ApiConsumer\LinkProcessor\UrlParser\UrlParser;
use Model\LinkModel;

class LinkProcessor
{

    /**
     * @var LinkResolver
     */
    protected $resolver;

    /**
     * @var LinkAnalyzer
     */
    protected $analyzer;

    /**
     * @var LinkModel
     */
    protected $linkModel;

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

    /**
     * @var UrlParser
     */
    protected $urlParser;


    public function __construct(
        LinkResolver $linkResolver,
        LinkAnalyzer $linkAnalyzer,
        LinkModel $linkModel,
        ScraperProcessor $scrapperProcessor,
        YoutubeProcessor $youtubeProcessor,
        SpotifyProcessor $spotifyProcessor,
        UrlParser $urlParser
    )
    {

        $this->resolver = $linkResolver;
        $this->analyzer = $linkAnalyzer;
        $this->linkModel = $linkModel;
        $this->scrapperProcessor = $scrapperProcessor;
        $this->youtubeProcessor = $youtubeProcessor;
        $this->spotifyProcessor = $spotifyProcessor;
        $this->urlParser = $urlParser;
    }

    /**
     * @param array $link
     * @return array
     */
    public function process(array $link)
    {
        if ($this->isLinkProcessed($link)) {
            return $link;
        }

        $link['url'] = $this->resolver->resolve($link['url']);
        $link['url']= $this->urlParser->cleanURL($link['url']);

        if ($this->isLinkProcessed($link)) {
            return $link;
        }

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

    private function isLinkProcessed ($link)
    {
        try {
            $storedLink = $this->linkModel->findLinkByUrl($link['url']);
            if ($storedLink && isset($storedLink['processed']) && $storedLink['processed'] == '1') {
                return true;
            }

        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

}
