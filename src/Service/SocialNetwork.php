<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Service;

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

    function __construct(LinkedinSocialNetworkModel $linkedinSocialNetworkModel)
    {
        $this->linkedinSocialNetworkModel = $linkedinSocialNetworkModel;
    }

    public function setSocialNetworksInfo($userId, $socialNetworks, LoggerInterface $logger = null)
    {
        foreach($socialNetworks as $resource => $profileUrl) {
            $this->setSocialNetworkInfo($userId, $resource, $profileUrl, $logger);
        }
    }

    protected function setSocialNetworkInfo($userId, $resource, $profileUrl, LoggerInterface $logger = null)
    {
        switch($resource)
        {
            case 'linkedin':
                $this->linkedinSocialNetworkModel->set($userId, $profileUrl, $logger);
                if($logger) {
                    $logger->info('linkedin social network info added for user ' . $userId . ' (' . $profileUrl . ')');
                }
                break;
        }
    }
}