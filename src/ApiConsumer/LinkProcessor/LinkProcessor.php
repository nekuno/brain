<?php

namespace ApiConsumer\LinkProcessor;

use ApiConsumer\LinkProcessor\Processor\FacebookProcessor;
use ApiConsumer\LinkProcessor\Processor\ProcessorInterface;
use ApiConsumer\LinkProcessor\Processor\ScraperProcessor;
use ApiConsumer\LinkProcessor\Processor\SpotifyProcessor;
use ApiConsumer\LinkProcessor\Processor\TwitterProcessor;
use ApiConsumer\LinkProcessor\Processor\YoutubeProcessor;
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

    public function __construct(
        LinkResolver $linkResolver,
        LinkAnalyzer $linkAnalyzer,
        LinkModel $linkModel,
        ScraperProcessor $scrapperProcessor,
        YoutubeProcessor $youtubeProcessor,
        SpotifyProcessor $spotifyProcessor,
        FacebookProcessor $facebookProcessor,
        TwitterProcessor $twitterProcessor
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
    }

    /**
     * @param PreprocessedLink $preprocessedLink
     * @param bool $reprocess
     * @return array
     */
    public function process($preprocessedLink, $reprocess = false)
    {
        $processings = 0;

        do {
            $preprocessedLink->setCanonical(null);

            if (!$reprocess && $this->isLinkProcessed($preprocessedLink)) {
                $link = $preprocessedLink->getLink();
                $link['url'] = $preprocessedLink->getFetched();
                return $link;
            }

            //sets canonical url
            if ($this->mustResolve($preprocessedLink)) {
                $preprocessedLink = $this->resolver->resolve($preprocessedLink);
            } else {
                $preprocessedLink->setCanonical($preprocessedLink->getFetched());
            }

            if (!$this->isProcessable($preprocessedLink)) {
                $link = $preprocessedLink->getLink();
                $link['processed'] = 0;
                $link['url'] = $this->cleanURL($preprocessedLink->getFetched());
                return $link;
            }

            $cleanURL = $this->cleanURL($preprocessedLink->getCanonical());
            $preprocessedLink->setCanonical($cleanURL);

            if (!$reprocess && $this->isLinkProcessed($preprocessedLink)) {
                $link = $preprocessedLink->getLink();
                $link['url'] = $preprocessedLink->getCanonical();
                return $link;
            }

            try {

                $processor = $this->selectProcessor($preprocessedLink->getCanonical());
                $link = $processor->process($preprocessedLink);
                $processings++;

            } catch (RequestException $e) {

                $link = $preprocessedLink->getLink();
                $link['processed'] = 0;
                return $link;
            }

            $preprocessedLink->setFetched($preprocessedLink->getCanonical());

        } while ($preprocessedLink->getCanonical() !== $cleanURL && $processings < 10);

        if (!isset($link['url'])) {
            $link = $this->scrapperProcessor->process($preprocessedLink);
            $link['url'] = $preprocessedLink->getCanonical();
        }

        if (isset($link['thumbnail'])){
            $link['thumbnail'] = $this->sanitizeImage($link['thumbnail']);
        }

        return $link;
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

        if (null == $link->getCanonical()) {
            return false;
        }

        return true;
    }

    /**
     *
     * @param PreprocessedLink $preprocessedLink
     * @return bool
     */
    private function mustResolve(PreprocessedLink $preprocessedLink)
    {
        $processorName = $this->analyzer->getProcessorName($preprocessedLink->getFetched());
        if ($processorName == LinkAnalyzer::SPOTIFY){
            return false;
        }

        return true;
    }

    public function cleanURL($url)
    {
        $processor = $this->selectProcessor($url);

        return $processor->getParser()->cleanURL($url);
    }

    /**
     * @param $url string
     * @return ProcessorInterface
     */
    private function selectProcessor($url)
    {
        $processorName = $this->analyzer->getProcessorName($url);

        switch ($processorName) {
            case LinkAnalyzer::YOUTUBE:
                $processor = $this->youtubeProcessor;
                break;
            case LinkAnalyzer::SPOTIFY:
                $processor = $this->spotifyProcessor;
                break;
            case LinkAnalyzer::FACEBOOK:
                $processor = $this->facebookProcessor;
                break;
            case LinkAnalyzer::TWITTER:
                $processor = $this->twitterProcessor;
                break;
            case LinkAnalyzer::SCRAPPER:
            default:
                $processor = $this->scrapperProcessor;
                break;
        }

        return $processor;
    }

    private function sanitizeImage($url)
    {
        $url = $this->cleanURL($url);
        $processorName = $this->analyzer->getProcessorName($url);
        try{
            switch ($processorName) {
                case LinkAnalyzer::YOUTUBE:
                    $isCorrectResponse = $this->youtubeProcessor->isValidImage($url);
                    break;
                case LinkAnalyzer::SPOTIFY:
                    $isCorrectResponse = $this->spotifyProcessor->isValidImage($url);
                    break;
                case LinkAnalyzer::FACEBOOK:
                    $isCorrectResponse = $this->facebookProcessor->isValidImage($url);
                    break;
                case LinkAnalyzer::TWITTER:
                    $isCorrectResponse = $this->twitterProcessor->isValidImage($url);
                    break;
                case LinkAnalyzer::SCRAPPER:
                default:
                    $isCorrectResponse = $this->scrapperProcessor->isValidImage($url);
                    break;
            }
        } catch (\Exception $e) {
            $isCorrectResponse = false;
        }

        return $isCorrectResponse ? $url : null;
    }

}
