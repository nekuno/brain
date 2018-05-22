<?php

namespace ApiConsumer\LinkProcessor;

use ApiConsumer\Factory\ProcessorFactory;
use ApiConsumer\Images\ImageAnalyzer;
use ApiConsumer\LinkProcessor\Processor\BatchProcessorInterface;
use ApiConsumer\LinkProcessor\Processor\ProcessorInterface;
use ApiConsumer\LinkProcessor\Processor\YoutubeProcessor\YoutubeVideoProcessor;
use ApiConsumer\LinkProcessor\UrlParser\FacebookUrlParser;
use Model\Link\Link;
use Model\Token\Token;
use Model\Token\TokensManager;

class LinkProcessor
{
    private $processorFactory;
    private $imageAnalyzer;
    private $tokensModel;
    private $batch = array();

    public function __construct(ProcessorFactory $processorFactory, ImageAnalyzer $imageAnalyzer, TokensManager $tokensModel)
    {
        $this->processorFactory = $processorFactory;
        $this->imageAnalyzer = $imageAnalyzer;
        $this->tokensModel = $tokensModel;
    }

    public function scrape(PreprocessedLink $preprocessedLink)
    {
        $scrapper = $this->processorFactory->getScrapperProcessor();

        $this->executeProcessing($preprocessedLink, $scrapper);

        $this->checkSecureSites($preprocessedLink);

        return $preprocessedLink->getLinks();
    }

    private function checkSecureSites(PreprocessedLink $preprocessedLink)
    {
        $fb_security_titles = array('Vérification de sécurité', 'Security Check', 'Security Check Required', 'Control de seguridad');
        $fb_types = array(FacebookUrlParser::FACEBOOK_PAGE, FacebookUrlParser::FACEBOOK_PROFILE);
        foreach ($preprocessedLink->getLinks() as $link) {
            if (in_array($preprocessedLink->getType(), $fb_types) && in_array($link->getTitle(), $fb_security_titles)) {
                $link->setProcessed(false);
            }
        }
    }

    public function process(PreprocessedLink $preprocessedLink)
    {
        $processor = $this->selectProcessor($preprocessedLink);

        if (null != $processor->getResourceOwner() && !$processor->getResourceOwner()->canRequestAsClient()){
            $this->fixToken($preprocessedLink);
        }

        if ($processor instanceof BatchProcessorInterface) {
            $links = $this->executeBatchProcessing($preprocessedLink, $processor);
        } else {
            $this->executeProcessing($preprocessedLink, $processor);

            if (!$preprocessedLink->getFirstLink()->isComplete()) {
                $this->scrape($preprocessedLink);
            }
            $links = $preprocessedLink->getLinks();
        }

        return $links;
    }

    protected function fixToken(PreprocessedLink $preprocessedLink)
    {
        if (!$this->needsToken($preprocessedLink)) {
            return;
        }

        $bestToken = $this->findBestToken($preprocessedLink);

        if (!empty($bestToken)) {
            $preprocessedLink->setToken($bestToken);
        }
    }

    protected function needsToken(PreprocessedLink $preprocessedLink)
    {
        $hasToken = null != $preprocessedLink->getToken() && $preprocessedLink->getToken() instanceof Token;

        if (!$hasToken) {
            return true;
        }

        $resource = LinkAnalyzer::getResource($preprocessedLink->getUrl());
        $isCorrectToken = $preprocessedLink->getToken()->getResourceOwner() == $resource;

        return !$isCorrectToken;
    }

    protected function findBestToken(PreprocessedLink $preprocessedLink)
    {
        $resource = LinkAnalyzer::getResource($preprocessedLink->getUrl());

        $token = $this->tokensModel->getByLikedUrl($preprocessedLink->getUrl(), $resource);

        if (null !== $token) {
            return $token;
        }

        $token = $this->tokensModel->getOneByResource($resource);

        return $token;
    }

    /**
     * @return Link[]
     */
    public function processLastLinks()
    {
        $links = array();
        foreach ($this->batch as $name => $batch) {
            /** @var BatchProcessorInterface $processor */
            $processor = $this->processorFactory->build($name);
            $links = array_merge($links, $processor->requestBatchLinks($batch));
        }

        return $links;
    }

    protected function selectProcessor(PreprocessedLink $preprocessedLink)
    {
        $processorName = LinkAnalyzer::getProcessorName($preprocessedLink);

        return $this->processorFactory->build($processorName);
    }

    protected function getThumbnails(PreprocessedLink $preprocessedLink, ProcessorInterface $processor, array $response)
    {
        $images = $processor->getImages($preprocessedLink, $response);

        return $thumbnails = $this->chooseThumbnails($images);
    }

    protected function chooseThumbnails(array $images)
    {
        $thumbnails = array(
            'small' => $this->imageAnalyzer->selectSmallThumbnail($images),
            'medium' => $this->imageAnalyzer->selectMediumThumbnail($images),
            'large' => $this->imageAnalyzer->selectLargeThumbnail($images),
        );

        return $thumbnails;
    }

    protected function executeProcessing(PreprocessedLink $preprocessedLink, ProcessorInterface $processor)
    {
        $preprocessedLink->getFirstLink()->setUrl($preprocessedLink->getUrl());

        $response = $processor->getResponse($preprocessedLink);

        $processor->hydrateLink($preprocessedLink, $response);
        $processor->addTags($preprocessedLink, $response);
        $processor->getSynonymousParameters($preprocessedLink, $response);

        if (!$preprocessedLink->getFirstLink()->getThumbnailLarge()) {
            $thumbnails = $this->getThumbnails($preprocessedLink, $processor, $response);

            $preprocessedLink->getFirstLink()->setThumbnail($thumbnails['large']);
            $preprocessedLink->getFirstLink()->setThumbnailMedium($thumbnails['medium']);
            $preprocessedLink->getFirstLink()->setThumbnailSmall($thumbnails['small']);
        }
    }

    protected function executeBatchProcessing(PreprocessedLink $preprocessedLink, BatchProcessorInterface $processor)
    {
        $processorName = LinkAnalyzer::getProcessorName($preprocessedLink);

        $this->batch[$processorName] = isset($this->batch[$processorName]) ? $this->batch[$processorName] : array();
        $this->batch[$processorName][] = $preprocessedLink;

        $links = array();
        if ($processor->needToRequest($this->batch[$processorName])) {
            $links = $processor->requestBatchLinks($this->batch[$processorName]);
            $this->batch[$processorName] = array();
        }

        return $links;
    }

    public function isLinkWorking($url)
    {
        if (!$url || $url === ''){
            return true;
        }
        
        $preprocessedLink = new PreprocessedLink($url);
        $processor = $this->selectProcessor($preprocessedLink);

        $needsSpecialCheck = $processor instanceof YoutubeVideoProcessor;
        if (!($needsSpecialCheck)){
            $processor = $this->processorFactory->buildScrapperProcessor('scrapper');
        }

        try {
            $isLinkWorking = $processor->isLinkWorking($url);
        } catch (\Exception $e)
        {
            $isLinkWorking = false;
        }

        return $isLinkWorking;
    }
}
