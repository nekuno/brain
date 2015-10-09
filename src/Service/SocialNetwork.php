<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Service;

use Model\User\SocialNetwork\LinkedinSocialNetworkModel;

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

    public function setSocialNetworksInfo($userId, $socialNetworks)
    {
        foreach($socialNetworks as $resource => $profileUrl) {
            $this->setSocialNetworkInfo($userId, $resource, $profileUrl);
        }
    }

    protected function setSocialNetworkInfo($userId, $resource, $profileUrl)
    {
        switch($resource)
        {
            case 'linkedin':
                $this->linkedinSocialNetworkModel->set($userId, $profileUrl);
                break;
        }
    }
}