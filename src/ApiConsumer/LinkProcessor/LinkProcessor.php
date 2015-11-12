<?php

namespace ApiConsumer\LinkProcessor;

use ApiConsumer\LinkProcessor\Processor\FacebookProcessor;
use ApiConsumer\LinkProcessor\Processor\ScraperProcessor;
use ApiConsumer\LinkProcessor\Processor\SpotifyProcessor;
use ApiConsumer\LinkProcessor\Processor\TwitterProcessor;
use ApiConsumer\LinkProcessor\Processor\YoutubeProcessor;
use ApiConsumer\LinkProcessor\UrlParser\UrlParser;
use ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser;
use GuzzleHttp\Exception\RequestException;
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
     * @var FacebookProcessor
     */
    protected $facebookProcessor;

    /**
     * @var TwitterProcessor
     */
    protected $twitterProcessor;

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
        FacebookProcessor $facebookProcessor,
        TwitterProcessor $twitterProcessor,
        UrlParser $urlParser
    ) {

        $this->resolver = $linkResolver;
        $this->analyzer = $linkAnalyzer;
        $this->linkModel = $linkModel;
        $this->scrapperProcessor = $scrapperProcessor;
        $this->youtubeProcessor = $youtubeProcessor;
        $this->spotifyProcessor = $spotifyProcessor;
        $this->facebookProcessor = $facebookProcessor;
        $this->twitterProcessor = $twitterProcessor;
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

        $processor = $this->scrapperProcessor;

        $link['url'] = $this->cleanURL($link, $processor);

        if ($this->isLinkProcessed($link)) {
            return $link;
        }

        try {
            $processedLink = $processor->process($link);
        } catch (RequestException $e) {

            $link['processed'] = 0;

            return $link;
        }

        if (!$processedLink) {
            $processedLink = $this->scrapperProcessor->process($link);
        }

        return $processedLink;
    }

    public function cleanExternalURLs($link)
    {
        return $this->cleanURL($link, $this->scrapperProcessor);
    }

    public function getLinkAnalyzer()
    {
        return $this->analyzer;
    }

    private function isLinkProcessed($link)
    {

        if (isset($link['processed']) && $link['processed'] == 1){
            return true;
        }

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

    private function cleanURL($link, &$processor)
    {

        $url = '';
        $processorName = $this->analyzer->getProcessor($link);

        switch ($processorName) {
            case LinkAnalyzer::YOUTUBE:
                $processor = $this->youtubeProcessor;
                $url = $this->youtubeProcessor->getParser()->cleanURL($link['url']);
                break;
            case LinkAnalyzer::SPOTIFY:
                $processor = $this->spotifyProcessor;
                $url = $this->spotifyProcessor->getParser()->cleanURL($link['url']);
                break;
            case LinkAnalyzer::FACEBOOK:
                $processor = $this->facebookProcessor;
                $url = $this->urlParser->cleanURL($link['url']);
                break;
            case LinkAnalyzer::TWITTER:
                $processor = $this->twitterProcessor;
                $url = $this->urlParser->cleanURL($link['url']);
                break;
            case LinkAnalyzer::SCRAPPER:
                $processor = $this->scrapperProcessor;
                $url = $this->urlParser->cleanURL($link['url']);
                break;
        }

        return $url;
    }

}
