<?php

namespace Service;


use Http\OAuth\Factory\ResourceOwnerFactory;
use Model\User\GhostUser\GhostUserManager;
use Model\User\LookUpModel;
use Model\User\SocialNetwork\SocialProfile;
use Model\User\SocialNetwork\SocialProfileManager;
use Model\User\TokensModel;
use Model\UserModel;

class UserAggregator
{
    protected $userModel;
    protected $ghostUserManager;
    protected $socialProfileManager;
    protected $resourceOwnerFactory;
    protected $lookUpModel;
    protected $amqpManager;

    public function __construct(UserModel $userModel,
                                GhostUserManager $ghostUserManager,
                                SocialProfileManager $socialProfileManager,
                                ResourceOwnerFactory $resourceOwnerFactory,
                                LookUpModel $lookUpModel,
                                AMQPManager $AMQPManager)

    {
        $this->userModel = $userModel;
        $this->ghostUserManager = $ghostUserManager;
        $this->socialProfileManager = $socialProfileManager;
        $this->resourceOwnerFactory = $resourceOwnerFactory;
        $this->lookUpModel = $lookUpModel;
        $this->amqpManager = $AMQPManager;
    }

    /**
     * @param $username
     * @param $resource
     * @param null $id
     * @return SocialProfile[]|null
     */
    public function addUser($username, $resource, $id = null)
    {
        if (!($username && $resource)){
            return null;
        }

        if (!in_array($resource, TokensModel::getResourceOwners())){
            //$output->writeln('Resource '.$resource.' not supported.');
            return null;
        }

        $resourceOwner = $this->resourceOwnerFactory->build($resource);

        //if not implemented for resource or request error when asking API
        try{
            $url = $resourceOwner->getProfileUrl(array('screenName'=>$username));
        } catch (\Exception $e){
            //$output->writeln('ERROR: Could not get profile url for user '.$username. ' and resource '.$resource);
            //$output->writeln('Reason: '.$e->getMessage());
            return null;
        }

        $socialProfiles = $this->socialProfileManager->getByUrl($url);

        if (count($socialProfiles) == 0) {

            //$output->writeln('Creating new social profile with url '. $url);

            if ($id) {
                $user = $this->userModel->getById((integer)$id, true);
                $id = $user['qnoow_id'];
                //$output->writeln('SUCCESS: Found user with id '.$id);
            } else {
                $user = $this->ghostUserManager->create();
                $id = $user->getId();
                //$output->writeln('SUCCESS: Created ghost user with id:' . $id);
            }

            $socialProfileArray = array($resource => $url);

            $this->lookUpModel->setSocialProfiles($socialProfileArray, $id);
            $this->lookUpModel->dispatchSocialNetworksAddedEvent($id, $socialProfileArray);

            $socialProfiles = $this->socialProfileManager->getByUrl($url);

        } else {
            //$output->writeln('Found an already existing social profile with url '.$url);
        }

        return $socialProfiles;
    }

    /**
     * @param SocialProfile[] $socialProfiles
     */
    public function enqueueFetching(array $socialProfiles)
    {
        foreach ($socialProfiles as $socialProfile) {
            $this->amqpManager->enqueueMessage(array(
                'userId' => $socialProfile->getUserId(),
                'resourceOwner' => $socialProfile->getResource(),
            ), 'brain.channel.user_aggregator');
        }
    }

}