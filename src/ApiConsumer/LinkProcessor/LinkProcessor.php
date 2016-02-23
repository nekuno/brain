<?php

namespace ApiConsumer\LinkProcessor;

use ApiConsumer\LinkProcessor\Processor\FacebookProcessor;
use ApiConsumer\LinkProcessor\Processor\ScraperProcessor;
use ApiConsumer\LinkProcessor\Processor\SpotifyProcessor;
use ApiConsumer\LinkProcessor\Processor\TwitterProcessor;
use ApiConsumer\LinkProcessor\Processor\YoutubeProcessor;
use ApiConsumer\LinkProcessor\UrlParser\UrlParser;
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
    )
    {

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
     * @param PreprocessedLink $preprocessedLink
     * @param bool $reprocess
     * @return array
     */
    public function process($preprocessedLink, $reprocess = false)
    {
        if (!$reprocess && $this->isLinkProcessed($preprocessedLink)) {
            return $preprocessedLink->getLink();
        }

        $preprocessedLink = $this->resolver->resolve($preprocessedLink);

        if (!$this->isProcessable($preprocessedLink)) {
            $link = $preprocessedLink->getLink();
            $link['processed'] = 0;
            return $link;
        }

        $processor = $this->scrapperProcessor;

        $cleanURL = $this->cleanURL($preprocessedLink->getCanonical(), $processor);
        $preprocessedLink->setCanonical($cleanURL);

        if (!$reprocess && $this->isLinkProcessed($preprocessedLink)) {
            $link = $preprocessedLink->getLink();
            $link['url'] = $preprocessedLink->getCanonical();
            return $link;
        }

        try {
            $link = $processor->process($preprocessedLink);
        } catch (RequestException $e) {

            $preprocessedLink['processed'] = 0;
            return $preprocessedLink;
        }

        if (!$link) {
            $link = $this->scrapperProcessor->process($preprocessedLink);
        }

        return $link;
    }

    public function cleanExternalURLs($link)
    {
        return $this->cleanURL($link, $this->scrapperProcessor);
    }

    public function getLinkAnalyzer()
    {
        return $this->analyzer;
    }

    /**
     * @param $link PreprocessedLink
     * @return bool
     */
    private function isLinkProcessed(PreprocessedLink $link)
    {

        $linkArray = $link->getLink();

        if (isset($linkArray['processed']) && $linkArray['processed'] == 1) {
            return true;
        }

        try {
            $toAnalyze = $link->getCanonical() ?: $link->getFetched();
            $storedLink = $this->linkModel->findLinkByUrl($toAnalyze);
            if ($storedLink && isset($storedLink['processed']) && $storedLink['processed'] == '1') {
                return true;
            }

        } catch (\Exception $e) {
            return false;
        }

        return false;
    }

    private function isProcessable(PreprocessedLink $link)
    {
        if (count($link->getExceptions()) > 0) {
            //TODO: Log exceptions
            return false;
        }

        if (null != $link->getStatusCode() && ($link->getStatusCode() > 400)) {
            return false;
        }

        if (null == $link->getCanonical())
        {
            return false;
        }

        return true;
    }

    private function cleanURL($url, &$processor)
    {
        $processorName = $this->analyzer->getProcessor($url);

        //TODO: Simplify, upcast getParser to AbstractProcessor
        switch ($processorName) {
            case LinkAnalyzer::YOUTUBE:
                $processor = $this->youtubeProcessor;
                $url = $this->youtubeProcessor->getParser()->cleanURL($url);
                break;
            case LinkAnalyzer::SPOTIFY:
                $processor = $this->spotifyProcessor;
                $url = $this->spotifyProcessor->getParser()->cleanURL($url);
                break;
            case LinkAnalyzer::FACEBOOK:
                $processor = $this->facebookProcessor;
                $url = $this->urlParser->cleanURL($url);
                break;
            case LinkAnalyzer::TWITTER:
                $processor = $this->twitterProcessor;
                $url = $this->urlParser->cleanURL($url);
                break;
            case LinkAnalyzer::SCRAPPER:
            default:
                $processor = $this->scrapperProcessor;
                $url = $this->urlParser->cleanURL($url);
                break;
        }

        return $url;
    }

}
