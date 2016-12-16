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
use Service\EventDispatcher;

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
            $this->manageUrlUnprocessed($e, sprintf('resolving url %s while processing for user %d', $preprocessedLink->getUrl(), $userId), $preprocessedLink->getUrl());

            $links = $this->save($preprocessedLink);
            $this->like($userId, $links, $preprocessedLink);

            return $links;
        } catch (UrlChangedException $e) {
        }

        if ($this->isLinkSavedAndProcessed($preprocessedLink)) {
            $link = $this->linkModel->findLinkByUrl($preprocessedLink->getUrl());
            $this->like($userId, array($link), $preprocessedLink);

            return null;
        }

        try {
            $this->processLink($preprocessedLink);
        } catch (UrlChangedException $e) {
            $preprocessedLink->setUrl($e->getNewUrl());

            return $this->fullProcessSingle($preprocessedLink, $userId);

        } catch (\Exception $e) {
            $this->manageError($e, sprintf('processing url %s for user %d', $preprocessedLink->getUrl(), $userId));

            return null;
        }

        $this->addSynonymous($preprocessedLink);
        $this->checkCreator($preprocessedLink);

        $links = $this->save($preprocessedLink);
        $this->like($userId, $links, $preprocessedLink);

        return $links;
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
            $this->manageError($e, sprintf('resolving url %s while reprocessing', $preprocessedLink->getUrl()));
            $link = $this->overwrite($preprocessedLink);

            return $link;
        } catch (UrlChangedException $e) {
            $this->manageChangedUrl($e->getOldUrl(), $e->getNewUrl());
        }

        try {
            $this->processLink($preprocessedLink);
            $links = $this->save($preprocessedLink);

            return $links;
        } catch (UrlChangedException $e) {

            $oldUrl = $e->getOldUrl();
            $newUrl = $e->getNewUrl();
            $this->manageChangedUrl($oldUrl, $newUrl);

            $preprocessedLink->setUrl($newUrl);

            return $this->fullReprocessSingle($preprocessedLink);

        } catch (\Exception $e) {
            $this->manageError($e, sprintf('saving link %s from resource %s', $preprocessedLink->getUrl(), $preprocessedLink->getSource()));

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
            $url = $preprocessedLink->getUrl();
            $this->manageUrlUnprocessed($e, sprintf('cleaning while processing %s', $url), $url);

            $links = $this->getUnprocessedLinks($preprocessedLink);
            foreach ($links as $link) {
                $preprocessedLink->addLink($link);
            }

            return;
        } catch (\Exception $e) {
            $this->manageError($e, sprintf('cleaning while processing %s', $preprocessedLink->getUrl()));

            $links = $this->getUnprocessedLinks($preprocessedLink);
            foreach ($links as $link) {
                $preprocessedLink->addLink($link);
            }

            return;
        }

        try {
            $links = $this->linkProcessor->process($preprocessedLink);
        } catch (CannotProcessException $e) {
            $links = array($this->scrape($preprocessedLink));
        } catch (RequestException $e) {
            $this->manageError($e, 'requesting while processing from linkProcessor');
            $links = array($this->scrape($preprocessedLink));
        }

        $preprocessedLink->setLinks($links);
    }

    private function scrape(PreprocessedLink $preprocessedLink)
    {
        try {
            return $this->linkProcessor->scrape($preprocessedLink);
        } catch (CannotProcessException $e) {
            $this->manageError($e, sprintf('scraping %s', $preprocessedLink->getUrl()));

            return $this->getUnprocessedLinks($preprocessedLink);
        }
    }

    private function isLinkSavedAndProcessed(PreprocessedLink $preprocessedLink)
    {
        try {
            $linkUrl = $preprocessedLink->getUrl() ?: $preprocessedLink->getUrl();
            $storedLink = $this->linkModel->findLinkByUrl($linkUrl);

            return $storedLink && isset($storedLink['processed']) && $storedLink['processed'] == '1';

        } catch (\Exception $e) {
            $this->manageError($e, sprintf('checking saved and processed for %s', $preprocessedLink->getUrl()));

            return false;
        }
    }

    private function checkCreator(PreprocessedLink $preprocessedLink)
    {
        foreach ($preprocessedLink->getLinks() as $link) {
            if ($link instanceof Creator && $preprocessedLink->getSource() == TokensModel::TWITTER) {
                try {
                    $username = (new TwitterUrlParser())->getProfileId($link->getUrl());
                    $this->dispatcher->dispatch(\AppEvents::CHANNEL_ADDED, new ChannelEvent(TokensModel::TWITTER, $link->getUrl(), $username));
                } catch (\Exception $e) {
                    $this->manageError($e, sprintf('checking creator for url %s', $link->getUrl()));
                }
            }
        }
    }

    private function addSynonymous(PreprocessedLink $preprocessedLink)
    {
        try {
            $synonymousPreprocessed = $this->fetcherService->fetchSynonymous($preprocessedLink->getSynonymousParameters());
        } catch (\Exception $e) {
            $this->manageError($e, sprintf('fetching synonymous for %s', $preprocessedLink->getUrl()));

            return;
        }

        foreach ($synonymousPreprocessed as $singleSynonymous) {
            $this->processLink($singleSynonymous);
            $preprocessedLink->getFirstLink()->addSynonymous($singleSynonymous->getFirstLink());
        }
    }

    private function save(PreprocessedLink $preprocessedLink)
    {
        $links = array();
        $this->readyToSave($preprocessedLink);
        foreach ($preprocessedLink->getLinks() as $link) {
            try {
                $linkCreated = $this->linkModel->addOrUpdateLink($link->toArray());
                $links[] = $linkCreated;
            } catch (\Exception $e) {
                $this->manageError($e, sprintf('saving link %s from resource %s', $preprocessedLink->getUrl(), $preprocessedLink->getSource()));

                continue;
            }
        }

        return $links;
    }

    private function readyToSave(PreprocessedLink $preprocessedLink)
    {
        foreach ($preprocessedLink->getLinks() as $link) {
            if (!$link->isComplete()) {
                //log
                $this->getUnprocessedLinks($preprocessedLink);
            }
        }

        return $preprocessedLink->getLinks();
    }

    private function overwrite(PreprocessedLink $preprocessedLink)
    {
        $links = $this->getUnprocessedLinks($preprocessedLink);
        $updatedLinks = array();

        foreach ($links as $link) {
            $this->linkModel->setProcessed($link->getUrl(), false);

            $linkArray = $link->toArray();
            $linkArray['tempId'] = $linkArray['url'];

            $updatedLinks[] = $this->linkModel->updateLink($linkArray);
        }

        return $updatedLinks;
    }

    private function getUnprocessedLinks(PreprocessedLink $preprocessedLink)
    {
        foreach ($preprocessedLink->getLinks() as $link) {
            $link->setProcessed(false);
        }

        return $preprocessedLink->getLinks();
    }

    private function like($userId, array $links, PreprocessedLink $preprocessedLink)
    {
        $likes = array();
        foreach ($links as $link) {
            $linkId = $link['id'];
            try {
                $like = $this->rateModel->userRateLink($userId, $linkId, $preprocessedLink->getSource(), null, RateModel::LIKE, false);
                $likes[] = $like;
            } catch (\Exception $e) {
                $this->manageError($e, sprintf('liking while processing link with id %d for user $d', $linkId, $userId));
            }
        }

        return $likes;
    }

    private function logNotice($message)
    {
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->notice($message);

            return true;
        }

        return false;
    }

    private function manageError(\Exception $e, $process)
    {
        $this->dispatcher->dispatchError($e, $process);

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->error($e->getMessage());

            if ($e instanceof Neo4jException) {
                $this->logger->error(sprintf('Query: %s' . "\n" . 'Data: %s', $e->getQuery(), print_r($e->getData(), true)));
            }

            return true;
        }

        return false;
    }

    private function manageUrlUnprocessed(\Exception $e, $process, $url)
    {
        $this->logNotice(sprintf('Error processing url %s while %s', $url, $process));
        $this->dispatcher->dispatchUrlUnprocessed($e, $process);
    }

}