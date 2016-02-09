<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace EventListener;


use Event\PrivacyEvent;
use Model\User\GroupModel;
use Model\User\InvitationModel;
use Model\User\ProfileModel;
use Model\UserModel;
use Service\TokenGenerator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PrivacySubscriber implements EventSubscriberInterface
{
    /**
     * @var GroupModel
     */
    protected $groupModel;

    /**
     * @var InvitationModel
     */
    protected $invitationModel;

    /**
     * @var TokenGenerator
     */
    protected $tokenGenerator;

    /**
     * @var UserModel
     */
    protected $userModel;

    /**
     * @var string
     */
    protected $socialhost;

    /**
     * @var ProfileModel
     */
    protected $profileModel;

    /**
     * PrivacySubscriber constructor.
     * @param GroupModel $groupModel
     * @param UserModel $userModel
     * @param ProfileModel $profileModel
     * @param InvitationModel $invitationModel
     * @param TokenGenerator $tokenGenerator
     * @param $socialhost
     */
    public function __construct(GroupModel $groupModel, UserModel $userModel, ProfileModel $profileModel, InvitationModel $invitationModel, TokenGenerator $tokenGenerator, $socialhost)
    {
        $this->groupModel = $groupModel;
        $this->userModel = $userModel;
        $this->profileModel = $profileModel;
        $this->invitationModel = $invitationModel;
        $this->tokenGenerator = $tokenGenerator;
        $this->socialhost = $socialhost;
    }

    public static function getSubscribedEvents()
    {
        return array(
            \AppEvents::PRIVACY_UPDATED => array('onPrivacyUpdated'),
        );
    }

    public function onPrivacyUpdated(PrivacyEvent $event)
    {
        $data = $event->getPrivacy();

        $groupsFollowers = $this->groupModel->getGroupFollowersFromInfluencerId($event->getUserId());

        if ($this->needsGroupFollowers($data)) {

            $influencerProfile = $this->profileModel->getById($event->getUserId());
            if (isset($influencerProfile['interfaceLanguage'])){
                $language = $influencerProfile['interfaceLanguage'];
            } else {
                $language = 'es';
            }

            if ($language == 'es'){
                $groupName = 'Tu grupo de seguidores';
            } else {
                $groupName = "Your group of followers";
            }

            $groupData = array(
                'date' => 4102444799000,
                'name' => $groupName,
                'html' => '<h3> '.$groupName.' </h3>',
                'location' => array(
                    'latitude' => 40.4167754,
                    'longitude' => -3.7037902,
                    'address' => 'Madrid',
                    'locality' => 'Madrid',
                    'country' => 'Spain'
                ),
                'followers' => true,
                'influencer_id' => $event->getUserId(),
                'min_matching' => $data['messages']['value'],
                'type_matching' => str_replace("min_", "", $data['messages']['key']),
            );

            if (isset($groupsFollowers[0])) {
                $groupId = $groupsFollowers[0];
                $group = $this->groupModel->update($groupId, $groupData);
                $this->invitationModel->setAvailableInvitations($group['invitation_token'], InvitationModel::MAX_AVAILABLE);
            } else {
                $group = $this->groupModel->create($groupData);
                $influencer = $this->userModel->getById($event->getUserId());
                $picture = isset($influencer['picture'])? $influencer['picture']:'media/cache/user_avatar_180x180/bundles/qnoowweb/images/user-no-img.jpg';

                $invitationData = array(
                    'userId' => $event->getUserId(),
                    'groupId' => $group['id'],
                    'available' => InvitationModel::MAX_AVAILABLE,
                );

                $invitationData['image_url'] = $this->socialhost.$picture;

                $this->invitationModel->create($invitationData, $this->tokenGenerator);
            }

        } else if (isset($groupsFollowers[0])) {

            $groupId = $groupsFollowers[0];
            $invitation = $this->invitationModel->getByGroupFollowersId($groupId);
            if ($invitation['invitation']['available'] > 0) {
                $this->invitationModel->setAvailableInvitations($invitation['invitation']['token'], 0);
            }

        }
    }

    /**
     * @param array $data
     * @return bool
     */
    protected function needsGroupFollowers(array $data)
    {
        if (isset($data['messages']['key']) && isset($data['messages']['value'])) {
            if (in_array($data['messages']['key'], array('min_compatibility', 'min_similarity'))) {
                if ($data['messages']['value'] >= 50) {
                    return true;
                }
            }
        }
        return false;
    }
}