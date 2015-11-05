<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Service;

use ApiConsumer\Factory\FetcherFactory;
use ApiConsumer\Fetcher\GoogleProfileFetcher;
use ApiConsumer\LinkProcessor\LinkAnalyzer;
use Model\User\LookUpModel;
use Model\User\SocialNetwork\LinkedinSocialNetworkModel;
use Psr\Log\LoggerInterface;

/**
 * SocialNetwork
 */
class SocialNetwork
{
    /**
     * @var LinkedinSocialNetworkModel
     */
    protected $linkedinSocialNetworkModel;

    /**
     * @var LookupModel
     */
    protected $lookupModel;

    /**
     * @var FetcherFactory
     */
    protected $fetcherFactory;

    function __construct(LinkedinSocialNetworkModel $linkedinSocialNetworkModel,
                         LookUpModel $lookupModel,
                         FetcherFactory $fetcherFactory)
    {
        $this->linkedinSocialNetworkModel = $linkedinSocialNetworkModel;
        $this->lookupModel = $lookupModel;
        $this->fetcherFactory = $fetcherFactory;
    }

    public function setSocialNetworksInfo($userId, $socialNetworks, LoggerInterface $logger = null)
    {
        foreach ($socialNetworks as $resource => $profileUrl) {
            $this->setSocialNetworkInfo($userId, $resource, $profileUrl, $logger, $socialNetworks);
        }
    }

    protected function setSocialNetworkInfo($userId, $resource, $profileUrl, LoggerInterface $logger = null, array $socialNetworks)
    {
        switch ($resource) {
            case 'linkedin':
                $this->linkedinSocialNetworkModel->set($userId, $profileUrl, $logger);
                if ($logger) {
                    $logger->info('linkedin social network info added for user ' . $userId . ' (' . $profileUrl . ')');
                }
                break;
            case 'googleplus':
                if (isset($socialNetworks['youtube'])) {
                    break;
                }

                if ($logger){
                    $logger->info('analyzing google plus profile for getting youtube profile');
                }

                /** @var GoogleProfileFetcher $googleProfileFetcher */
                $googleProfileFetcher = $this->fetcherFactory->build('google_profile');
                $googleId = $googleProfileFetcher->getResourceOwner()->getUsername(array('url' => $profileUrl));
                $profiles = $googleProfileFetcher->fetchLinksFromUserFeed(array('googleId' => $googleId), true);

                if (count($profiles) !== 1) {
                    break;
                }
                $urls = $profiles[0]->urls;
                foreach ($urls as $url) {
                    if (strpos('youtube.com', $url)) {
                        $socialProfile = array(array(LinkAnalyzer::YOUTUBE => $url));
                        $this->lookupModel->setSocialProfiles($socialProfile, $userId);
                        $this->lookupModel->dispatchSocialNetworksAddedEvent($userId, $socialProfile);
                        if ($logger){
                            $logger->info('Youtube url '.$url. 'found.');
                        }
                    }
                }
        }
    }
}