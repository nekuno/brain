<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\Event\ChannelEvent;
use ApiConsumer\Exception\CannotProcessException;
use ApiConsumer\Exception\CouldNotResolveException;
use ApiConsumer\Exception\UrlChangedException;
use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\LinkProcessor\LinkAnalyzer;
use ApiConsumer\LinkProcessor\LinkProcessor;
use ApiConsumer\LinkProcessor\LinkResolver;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser;
use Event\ConsistencyEvent;
use Event\ProcessLinkEvent;
use Event\ProcessLinksEvent;
use GuzzleHttp\Exception\RequestException;
use Model\Creator;
use Model\LinkModel;
use Model\Neo4j\Neo4jException;
use Model\User\RateModel;
use Model\User\TokensModel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class ProcessorService implements LoggerAwareInterface
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected $fetcherService;

    protected $linkModel;

    protected $dispatcher;

    protected $rateModel;

    protected $resolver;

    protected $linkProcessor;

    /**
     * ProcessorService constructor.
     * @param FetcherService $fetcherService
     * @param LinkProcessor $linkProcessor
     * @param LinkModel $linkModel
     * @param EventDispatcher $dispatcher
     * @param RateModel $rateModel
     */
    public function __construct(FetcherService $fetcherService, LinkProcessor $linkProcessor, LinkModel $linkModel, EventDispatcher $dispatcher, RateModel $rateModel, LinkResolver $resolver)
    {
        $this->fetcherService = $fetcherService;
        $this->linkProcessor = $linkProcessor;
        $this->linkModel = $linkModel;
        $this->dispatcher = $dispatcher;
        $this->rateModel = $rateModel;
        $this->resolver = $resolver;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param PreprocessedLink[] $preprocessedLinks
     * @param int $userId
     * @return array
     */
    public function process(array $preprocessedLinks, $userId)
    {
        if (empty($preprocessedLinks)) {
            return array();
        }
        $source = $this->getCommonSource($preprocessedLinks);
        $this->dispatcher->dispatch(\AppEvents::PROCESS_START, new ProcessLinksEvent($userId, $source, $preprocessedLinks));

        $links = array();
        foreach ($preprocessedLinks as $key => $preprocessedLink) {

            $this->dispatcher->dispatch(\AppEvents::PROCESS_LINK, new ProcessLinkEvent($userId, $source, $preprocessedLink));

            $link = $this->fullProcessSingle($preprocessedLink, $userId);

            if ($link) {
                $links[$key] = $link;
            }
        }

        $this->dispatcher->dispatch(\AppEvents::PROCESS_FINISH, new ProcessLinksEvent($userId, $source, $preprocessedLinks));

        return $links;
    }

    private function fullProcessSingle(PreprocessedLink $preprocessedLink, $userId)
    {
        try {
            $this->resolve($preprocessedLink);
        } catch (CouldNotResolveException $e) {
            $preprocessedLink->setLink($this->getUnprocessedLink($preprocessedLink));
            $link = $this->save($preprocessedLink, $userId);

            return $link;
        } catch (UrlChangedException $e) {
        }

        try {
            if ($this->isLinkSavedAndProcessed($preprocessedLink)) {
                return null;
            }

            $this->processLink($preprocessedLink);

            $this->addSynonymous($preprocessedLink);
            $this->checkCreator($preprocessedLink);

            $link = $this->save($preprocessedLink, $userId);

            return $link;

        } catch (UrlChangedException $e) {
            $preprocessedLink->setUrl($e->getNewUrl());

            return $this->fullProcessSingle($preprocessedLink, $userId);

        } catch (\Exception $e) {
            if ($e instanceof Neo4jException) {
                //log
            }

            return null;
        }
    }

    /**
     * @param PreprocessedLink[] $preprocessedLinks
     * @return array
     */
    public function reprocess(array $preprocessedLinks)
    {
        $links = array();
        foreach ($preprocessedLinks as $key => $preprocessedLink) {
            $this->logNotice(sprintf('Reprocessing link %s', $preprocessedLink->getUrl()));
            $link = $this->fullReprocessSingle($preprocessedLink);

            if ($link) {
                $links[$key] = $link;
            }
        }

        return $links;
    }

    private function fullReprocessSingle(PreprocessedLink $preprocessedLink)
    {
        try {
            $this->resolve($preprocessedLink);
        } catch (CouldNotResolveException $e) {
            $link = $this->overwrite($preprocessedLink);

            return $link;
        } catch (UrlChangedException $e) {
            $this->manageChangedUrl($e->getOldUrl(), $e->getNewUrl());
        }

        try {
            $this->processLink($preprocessedLink);

            $link = $this->readyToSave($preprocessedLink);
            $this->linkModel->setProcessed($link->getUrl(), false);
            $linkCreated = $this->linkModel->addOrUpdateLink($link->toArray());

            return $linkCreated;

        } catch (Neo4jException $e) {
            $this->logError(sprintf('Query: %s' . "\n" . 'Data: %s', $e->getQuery(), print_r($e->getData(), true)));

            return null;
        } catch (UrlChangedException $e) {

            $oldUrl = $e->getOldUrl();
            $newUrl = $e->getNewUrl();
            $this->manageChangedUrl($oldUrl, $newUrl);

            $preprocessedLink->setUrl($newUrl);

            return $this->fullReprocessSingle($preprocessedLink);

        } catch (\Exception $e) {
            $this->logError(sprintf('Fetcher: Unexpected error processing link "%s" from resource "%s". Reason: %s', $preprocessedLink->getUrl(), $preprocessedLink->getSource(), $e->getMessage()));

            return null;
        }
    }

    private function manageChangedUrl($oldUrl, $newUrl)
    {
        if ($this->linkModel->findLinkByUrl($newUrl)) {
            $fusedLink = $this->linkModel->fuseLinks($oldUrl, $newUrl);
            $this->dispatcher->dispatch(\AppEvents::CONSISTENCY_LINK, new ConsistencyEvent($fusedLink['id']));
        } else {
            $this->linkModel->setProcessed($oldUrl, false);
            $this->linkModel->changeUrl($oldUrl, $newUrl);
        }
    }

    private function getCommonSource(array $preprocessedLinks)
    {
        if (empty($preprocessedLinks)) {
            $source = null;
        } else {
            $source = reset($preprocessedLinks)->getSource();
        }

        return $source;
    }

    private function resolve(PreprocessedLink $preprocessedLink)
    {
        if (!LinkAnalyzer::mustResolve($preprocessedLink)) {
            return;
        }

        $resolution = $this->resolver->resolve($preprocessedLink);

        if (null == $resolution->getFinalUrl()) {
            throw new CouldNotResolveException($preprocessedLink->getUrl());
        }

        $preprocessedLink->setUrl($resolution->getFinalUrl());

        if ($resolution->getStartingUrl() !== $resolution->getFinalUrl()) {
            throw new UrlChangedException($resolution->getStartingUrl(), $resolution->getFinalUrl());
        }
    }

    private function processLink(PreprocessedLink $preprocessedLink)
    {
        try {
            $cleanURL = LinkAnalyzer::cleanUrl($preprocessedLink->getUrl());
            $preprocessedLink->setUrl($cleanURL);
        } catch (UrlNotValidException $e) {
            //log
            $link = $this->getUnprocessedLink($preprocessedLink);
            $preprocessedLink->setLink($link);

            return;
        }

        try {
            $link = $this->linkProcessor->process($preprocessedLink);
        } catch (CannotProcessException $e) {
            $link = $this->scrape($preprocessedLink);
        } catch (RequestException $e) {
            $link = $this->scrape($preprocessedLink);
        }

        $preprocessedLink->setLink($link);
    }

    private function scrape(PreprocessedLink $preprocessedLink)
    {
        try {
            return $this->linkProcessor->scrape($preprocessedLink);
        } catch (CannotProcessException $e) {
            return $this->getUnprocessedLink($preprocessedLink);
        }
    }

    private function isLinkSavedAndProcessed(PreprocessedLink $preprocessedLink)
    {
        try {
            $linkUrl = $preprocessedLink->getUrl() ?: $preprocessedLink->getUrl();
            $storedLink = $this->linkModel->findLinkByUrl($linkUrl);

            return $storedLink && isset($storedLink['processed']) && $storedLink['processed'] == '1';

        } catch (\Exception $e) {
            //log
            return false;
        }
    }

    private function checkCreator(PreprocessedLink $preprocessedLink)
    {
        $link = $preprocessedLink->getLink();

        if ($link instanceof Creator && $preprocessedLink->getSource() == TokensModel::TWITTER) {
            $username = (new TwitterUrlParser())->getProfileId($link->getUrl());
            $this->dispatcher->dispatch(\AppEvents::CHANNEL_ADDED, new ChannelEvent(TokensModel::TWITTER, $link->getUrl(), $username));
        }
    }

    private function addSynonymous(PreprocessedLink $preprocessedLink)
    {
        $synonymousPreprocessed = $this->fetcherService->fetchSynonymous($preprocessedLink->getSynonymousParameters());

        foreach ($synonymousPreprocessed as $singleSynonymous) {
            try {
                $this->processLink($singleSynonymous);
                $preprocessedLink->getLink()->addSynonymous($singleSynonymous->getLink());
            } catch (CannotProcessException $e) {
                //TODO: log
            }
        }
    }

    private function save(PreprocessedLink $preprocessedLink, $userId)
    {
        $link = $this->readyToSave($preprocessedLink);

        try {
            $linkCreated = $this->linkModel->addOrUpdateLink($link->toArray());
            $this->rateModel->userRateLink($userId, $linkCreated['id'], $preprocessedLink->getSource(), null, RateModel::LIKE, false);
        } catch (Neo4jException $e) {
            $this->logError(sprintf('Query: %s' . "\n" . 'Data: %s', $e->getQuery(), print_r($e->getData(), true)));

            return array();
        } catch (\Exception $e) {
            $this->logError(sprintf('Fetcher: Unexpected error processing link "%s" from resource "%s". Reason: %s', $preprocessedLink->getUrl(), $preprocessedLink->getSource(), $e->getMessage()));

            return array();
        }

        return $linkCreated;
    }

    private function readyToSave(PreprocessedLink $preprocessedLink)
    {
        $link = $preprocessedLink->getLink();

        if (!$link->isComplete()) {
            //log
            $link = $this->getUnprocessedLink($preprocessedLink);
        }

        return $link;
    }

    private function overwrite(PreprocessedLink $preprocessedLink)
    {
        $link = $this->getUnprocessedLink($preprocessedLink);
        $this->linkModel->setProcessed($link->getUrl(), false);

        $linkArray = $link->toArray();
        $linkArray['tempId'] = $linkArray['url'];

        return $this->linkModel->updateLink($linkArray);
    }

    private function getUnprocessedLink(PreprocessedLink $preprocessedLink)
    {
        $link = $preprocessedLink->getLink();
        $link->setProcessed(false);

        return $link;
    }

    private function logNotice($message)
    {
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->notice($message);

            return true;
        }

        return false;
    }

    private function logError($message)
    {
        //dispatch log

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->error($message);

            return true;
        }

        return false;
    }

}