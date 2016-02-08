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

        if ($this->needsGroupFollowers($data)) {

            $groupsFollowers = $this->groupModel->getGroupFollowersFromInfluencerId($event->getUserId());

            if ($data['messages']['value'] >= 50) {

                $groupData = array(
                    'followers' => true,
                    'influencer_id' => $event->getUserId(),
                );

                if (!empty($groupsFollowers)) {
                    $groupId = $groupsFollowers[0];
                    $this->groupModel->update($groupId, $groupData);
                } else {
                    $groupNode = $this->groupModel->create($groupData);

                    $invitationData = array(
                        'userId' => $event->getUserId(),
                        'groupId' => $groupNode->getId(),
                    );
                    $invitation = $this->invitationModel->create($invitationData, $this->tokenGenerator);

                    $this->invitationModel->setAvailableInvitations($invitation['token'], InvitationModel::MAX_AVAILABLE);
                }

            } else if (!empty($groupsFollowers)) {
                $groupId = $groupsFollowers[0];
                $invitation = $this->invitationModel->getByGroupFollowersId($groupId);
                if ($invitation['available'] > 0) {
                    $this->invitationModel->setAvailableInvitations($invitation['token'], 0);
                }

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
                return true;
            }
        }
        return false;
    }
}