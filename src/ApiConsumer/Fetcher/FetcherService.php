<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\Exception\PaginatedFetchingException;
use ApiConsumer\Factory\FetcherFactory;
use ApiConsumer\LinkProcessor\LinkAnalyzer;
use ApiConsumer\LinkProcessor\SynonymousParameters;
use ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser;
use Event\ExceptionEvent;
use Event\FetchEvent;
use GuzzleHttp\Exception\RequestException;
use Model\SocialNetwork\SocialProfileManager;
use Model\Token\Token;
use Model\Token\TokenManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FetcherService implements LoggerAwareInterface
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var FetcherFactory
     */
    protected $fetcherFactory;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var TokenManager
     */
    protected $tokensModel;

    /**
     * @var SocialProfileManager
     */
    protected $socialProfileManager;

    /**
     * @var array
     */
    protected $options;

    public function __construct(
        FetcherFactory $fetcherFactory,
        EventDispatcherInterface $dispatcher,
        TokenManager $tokensModel,
        SocialProfileManager $socialProfileManager,
        array $options
    ) {
        $this->fetcherFactory = $fetcherFactory;
        $this->dispatcher = $dispatcher;
        $this->tokensModel = $tokensModel;
        $this->socialProfileManager = $socialProfileManager;
        $this->options = $options;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function fetchAllConnected($userId, $exclude = array())
    {
        $tokens = $this->tokensModel->getById($userId);

        $links = array();
        foreach ($tokens as $token) {
            $resourceOwner = $token->getResourceOwner();
            $fetchers = $this->chooseFetchers($resourceOwner, $exclude);

            foreach ($fetchers as $fetcher) {
                $links = array_merge($links, $this->fetchSingle($fetcher, $token, $resourceOwner));
            }
        }

        return $links;
    }

    public function fetchUser($userId, $resourceOwner, $exclude = array())
    {
        $this->dispatcher->dispatch(\AppEvents::FETCH_START, new FetchEvent($userId, $resourceOwner));

        $fetchers = $this->chooseFetchers($resourceOwner, $exclude);

        try {
            $links = $this->fetchFromToken($userId, $resourceOwner, $fetchers);
        } catch (NotFoundHttpException $e) {
            $links = $this->fetchFromSocialProfile($userId, $resourceOwner, $fetchers);
        }

        $this->dispatcher->dispatch(\AppEvents::FETCH_FINISH, new FetchEvent($userId, $resourceOwner));

        return $links;
    }

    public function fetchAsClient($userId, $resourceOwner, $exclude = array())
    {
        $fetchers = $this->chooseFetchers($resourceOwner, $exclude);
        $links = $this->fetchFromSocialProfile($userId, $resourceOwner, $fetchers);

        return $links;
    }

    private function fetchFromToken($userId, $resourceOwner, $fetchers)
    {
        $links = array();
        $token = $this->tokensModel->getByIdAndResourceOwner($userId, $resourceOwner);

        foreach ($fetchers as $fetcher) {
            $links = array_merge($links, $this->fetchSingle($fetcher, $token, $resourceOwner));
        }

        return $links;
    }

    private function fetchFromSocialProfile($userId, $resourceOwner, $fetchers)
    {
        $links = array();
        $socialProfiles = $this->socialProfileManager->getSocialProfiles($userId, $resourceOwner);

        //TODO: If count(socialProfiles) > 1, log, not always error
        foreach ($socialProfiles as $socialProfile) {
            $url = $socialProfile->getUrl();

            $username = LinkAnalyzer::getUsername($url);

            foreach ($fetchers as $fetcher) {
                $links = array_merge($links, $this->fetchPublic($fetcher, $userId, $username, $resourceOwner));
            }
        }

        return $links;
    }

    private function fetchSingle(FetcherInterface $fetcher, Token $token, $resourceOwner)
    {
        $userId = $token->getUserId();

        try {
            $links = $fetcher->fetchLinksFromUserFeed($token);

            return $links;
        } catch (PaginatedFetchingException $e) {
            //TODO: Improve exception management between services, controllers, workers...
            $originalException = $e->getOriginalException();
            if ($originalException instanceof RequestException && $originalException->getCode() == 429) {
                if ($resourceOwner == TokenManager::TWITTER) {
                    $this->logger->warning('Pausing for 15 minutes due to Too Many Requests Error');
                    sleep(15 * 60);
                }
            }
            $newLinks = $e->getLinks();
            $this->logger->warning(sprintf('Fetcher: Error fetching feed for user "%s" with fetcher "%s" from resource "%s". Reason: %s', $userId, get_class($fetcher), $resourceOwner, $originalException->getMessage()));
            $this->dispatcher->dispatch(\AppEvents::EXCEPTION_WARNING, new ExceptionEvent($e, sprintf('Fetcher: Error fetching feed for user "%s" with fetcher "%s" from resource "%s". Reason: %s', $userId, get_class($fetcher), $resourceOwner, $originalException->getMessage())));
            $this->logger->info(sprintf('%d links were fetched before the exception happened and are going to be processed.', count($newLinks)));

            return $newLinks;

        } catch (\Exception $e) {
            $this->logger->error(sprintf('Fetcher: Error fetching feed for user "%s" with fetcher "%s" from resource "%s". Reason: %s', $userId, get_class($fetcher), $resourceOwner, $e->getMessage()));

            return array();
        }
    }

    private function fetchPublic(FetcherInterface $fetcher, $userId, $username, $resourceOwner)
    {
        try {
            $links = $fetcher->fetchAsClient($username);

            return $links;
        } catch (PaginatedFetchingException $e) {
            //TODO: Improve exception management between services, controllers, workers...
            $originalException = $e->getOriginalException();
            if ($originalException instanceof RequestException && $originalException->getCode() == 429) {
                if ($resourceOwner == TokenManager::TWITTER) {
                    $this->logger->warning('Pausing for 15 minutes due to Too Many Requests Error');
                    sleep(15 * 60);
                }
            }
            $newLinks = $e->getLinks();
            $this->logger->warning(sprintf('Fetcher: Error fetching feed for user "%s" with fetcher "%s" from resource "%s". Reason: %s', $userId, get_class($fetcher), $resourceOwner, $originalException->getMessage()));
            $this->dispatcher->dispatch(\AppEvents::EXCEPTION_WARNING, new ExceptionEvent($e, sprintf('Fetcher: Error fetching feed for user "%s" with fetcher "%s" from resource "%s". Reason: %s', $userId, get_class($fetcher), $resourceOwner, $originalException->getMessage())));
            $this->logger->info(sprintf('%d links were fetched before the exception happened and are going to be processed.', count($newLinks)));

            return $newLinks;

        } catch (\Exception $e) {
            $this->logger->error(sprintf('Fetcher: Error fetching feed for user "%s" with fetcher "%s" from resource "%s". Reason: %s', $userId, get_class($fetcher), $resourceOwner, $e->getMessage()));

            return array();
        }
    }

    /**
     * @param SynonymousParameters $parameters
     * @return \ApiConsumer\LinkProcessor\PreprocessedLink[]
     */
    public function fetchSynonymous(SynonymousParameters $parameters)
    {
        switch ($parameters->getType()) {
            case YoutubeUrlParser::VIDEO_URL:
                /** @var YoutubeFetcher $fetcher */
                $fetcher = $this->fetcherFactory->build('youtube');
                $synonymous = $fetcher->fetchVideos($parameters);
                break;
            default:
                $synonymous = array();
                break;
        }

        return $synonymous;
    }

    /**
     * @param $resourceOwner
     * @param array $exclude
     * @return FetcherInterface[]
     */
    protected function chooseFetchers($resourceOwner, $exclude = array())
    {
        $fetchers = array();
        foreach ($this->options as $fetcherName => $fetcherConfig) {

            if (in_array($fetcherName, $exclude)) {
                continue;
            }

            if ($fetcherConfig['resourceOwner'] === $resourceOwner) {
                $fetchers[] = $this->fetcherFactory->build($fetcherName);
            }
        }

        return $fetchers;
    }
}
