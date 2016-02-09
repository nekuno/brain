<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace EventListener;


use Event\PrivacyEvent;
use Model\User\GroupModel;
use Model\User\InvitationModel;
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
     * PrivacySubscriber constructor.
     * @param GroupModel $groupModel
     * @param InvitationModel $invitationModel
     * @param TokenGenerator $tokenGenerator
     */
    public function __construct(GroupModel $groupModel, InvitationModel $invitationModel, TokenGenerator $tokenGenerator)
    {
        $this->groupModel = $groupModel;
        $this->invitationModel = $invitationModel;
        $this->tokenGenerator = $tokenGenerator;
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

            $groupData = array(
                'date' => 4102444799000,
                'name' => 'Your group of followers',
                'html' => '<h3> Your group of followers </h3>',
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

                $invitationData = array(
                    'userId' => $event->getUserId(),
                    'groupId' => $group['id'],
                    'available' => InvitationModel::MAX_AVAILABLE,
                );
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