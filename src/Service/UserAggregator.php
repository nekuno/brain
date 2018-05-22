<?php

namespace Service;


use ApiConsumer\Factory\ResourceOwnerFactory;
use ApiConsumer\ResourceOwner\TwitterResourceOwner;
use Model\GhostUser\GhostUserManager;
use Model\LookUp\LookUpManager;
use Model\SocialNetwork\SocialProfile;
use Model\SocialNetwork\SocialProfileManager;
use Model\Token\TokensManager;
use Model\User\UserManager;

class UserAggregator
{
    protected $userManager;
    protected $ghostUserManager;
    protected $socialProfileManager;
    protected $resourceOwnerFactory;
    protected $socialNetworkService;
    protected $lookUpModel;
    protected $amqpManager;

    public function __construct(UserManager $userManager,
                                GhostUserManager $ghostUserManager,
                                SocialProfileManager $socialProfileManager,
                                ResourceOwnerFactory $resourceOwnerFactory,
                                SocialNetwork $socialNetworkService,
	                            LookUpManager $lookUpModel,
                                AMQPManager $AMQPManager)

    {
        $this->userManager = $userManager;
        $this->ghostUserManager = $ghostUserManager;
        $this->socialProfileManager = $socialProfileManager;
        $this->resourceOwnerFactory = $resourceOwnerFactory;
        $this->socialNetworkService = $socialNetworkService;
        $this->lookUpModel = $lookUpModel;
        $this->amqpManager = $AMQPManager;
    }

    /**
     * @param $username
     * @param $resource
     * @param null $id
     * @param null $url
     * @return SocialProfile[]|null
     */
    public function addUser($username, $resource, $id = null, $url = null)
    {
        if (!($username && $resource)){
            return null;
        }

        if (!in_array($resource, TokensManager::getResourceOwners()) && !$url){
            //$output->writeln('Resource '.$resource.' not supported.');
            return null;
        }
	    if (in_array($resource, TokensManager::getResourceOwners())) {
            /** @var TwitterResourceOwner $resourceOwner */
            $resourceOwner = $this->resourceOwnerFactory->build($resource);

		    //if not implemented for resource or request error when asking API
		    try {
			    $url = $resourceOwner->getUserUrl($username);
		    } catch (\Exception $e){
			    //$output->writeln('ERROR: Could not get profile url for user '.$username. ' and resource '.$resource);
			    //$output->writeln('Reason: '.$e->getMessage());
			    return null;
		    }
	    }

		if (!$url) {
			//$output->writeln('url does not exists');
			return null;
		}

        $socialProfiles = $this->socialProfileManager->getByUrl($url);

        if (count($socialProfiles) == 0) {

            $socialProfileArray = array($resource => $url);
            //$output->writeln('Creating new social profile with url '. $url);

            $isExistingUser = null !== $id;
            if ($isExistingUser) {
                $user = $this->userManager->getById((integer)$id, true);
                $id = $user->getId();

                $this->lookUpModel->setSocialProfiles($socialProfileArray, $id);
                $this->socialNetworkService->setSocialNetworksInfo($id, $socialProfileArray);
                //$output->writeln('SUCCESS: Found user with id '.$id);
            } else {
                $user = $this->ghostUserManager->create();
                $id = $user->getId();

                $this->lookUpModel->setSocialProfiles($socialProfileArray, $id);
                //$output->writeln('SUCCESS: Created ghost user with id:' . $id);
            }

            $socialProfiles = $this->socialProfileManager->getByUrl($url);

        } else {
            //$output->writeln('Found an already existing social profile with url '.$url);
        }

        return $socialProfiles;
    }

    /**
     * @param SocialProfile[] $socialProfiles
     * @param $username
     * @param bool $force
     */
    public function enqueueChannel(array $socialProfiles, $username, $force = false)
    {
        foreach ($socialProfiles as $socialProfile) {
	        if ($socialProfile->getResource() == TokensManager::TWITTER || $socialProfile->getResource() == TokensManager::GOOGLE) {
	            $userId = $socialProfile->getUserId();
	            $resource = $socialProfile->getResource();

	            if (!$force && $this->userManager->isChannel($userId, $resource)){
	                continue;
	            }

	            $this->userManager->setAsChannel($userId, $resource);

                $messageData = array(
                    'userId' => $socialProfile->getUserId(),
                    'resourceOwner' => $socialProfile->getResource(),
                    'username' => $username,
                );
                $this->amqpManager->enqueueChannel($messageData);
            }
        }
    }

}