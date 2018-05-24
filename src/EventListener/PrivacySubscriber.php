<?php

namespace EventListener;

use Event\PrivacyEvent;
use Model\Group\GroupManager;
use Model\Invitation\InvitationManager;
use Model\Profile\ProfileManager;
use Model\User\UserManager;
use Service\GroupService;
use Symfony\Component\Translation\Translator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

class PrivacySubscriber implements EventSubscriberInterface
{
    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var GroupManager
     */
    protected $groupModel;

    /**
     * @var GroupService
     */
    protected $groupService;

    /**
     * @var InvitationManager
     */
    protected $invitationModel;

    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @var string
     */
    protected $socialhost;

    /**
     * @var ProfileManager
     */
    protected $profileModel;

    /**
     * PrivacySubscriber constructor.
     * @param TranslatorInterface $translator
     * @param GroupManager $groupModel
     * @param GroupService $groupService
     * @param UserManager $userManager
     * @param ProfileManager $profileModel
     * @param InvitationManager $invitationModel
     * @param string $socialHost
     * @param $socialHost
     */
    public function __construct(TranslatorInterface $translator, GroupManager $groupModel, GroupService $groupService, UserManager $userManager, ProfileManager $profileModel, InvitationManager $invitationModel, $socialHost)
    {
        $this->translator = $translator;
        $this->groupModel = $groupModel;
        $this->groupService = $groupService;
        $this->userManager = $userManager;
        $this->profileModel = $profileModel;
        $this->invitationModel = $invitationModel;
        $this->socialhost = $socialHost;
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
        $influencerId = $event->getUserId();

        $groupsFollowers = $this->groupModel->getGroupFollowersFromInfluencerId($influencerId);

        if ($this->needsGroupFollowers($data)) {
            $influencer = $this->userManager->getById($influencerId);
            $interfaceLanguage = $this->profileModel->getInterfaceLocale($influencerId);
            $this->translator->setLocale($interfaceLanguage);

            $groupName = $this->translator->trans('followers.group_name', array('%username%' => $influencer->getUsername()));
            $typeMatching = str_replace("min_", "", $data['messages']['key']);
            $groupData = array(
                'date' => 4102444799000,
                'name' => $groupName,
                'html' => '<h3> ' . $groupName . ' </h3>',
                'location' => array(
                    'latitude' => 40.4167754,
                    'longitude' => -3.7037902,
                    'address' => 'Madrid',
                    'locality' => 'Madrid',
                    'country' => 'Spain'
                ),
                'followers' => true,
                'influencer_id' => $influencerId,
                'min_matching' => $data['messages']['value'],
                'type_matching' => $typeMatching,
            );

            if (isset($groupsFollowers[0])) {
                $groupId = $groupsFollowers[0];
                $group = $this->groupService->updateGroup($groupId, $groupData);
                $this->invitationModel->setAvailableInvitations($group->getInvitation()['invitation_token'], InvitationManager::MAX_AVAILABLE);
            } else {
                $group = $this->groupService->createGroup($groupData);
                $url = $influencer->getPhoto()->getUrl();
                $compatibleOrSimilar = $typeMatching === 'compatibility' ? 'compatible' : 'similar';
                $slogan = $this->translator->trans('followers.invitation_slogan', array('%username%' => $influencer->getUsername(), '%compatible_or_similar%' => $compatibleOrSimilar));
                $invitationData = array(
                    'userId' => $influencerId,
                    'groupId' => $group->getId(),
                    'available' => InvitationManager::MAX_AVAILABLE,
                    'slogan' => $slogan,
                    'image_url' => $url,
                );

                $this->invitationModel->create($invitationData);
            }

        } else {
            if (isset($groupsFollowers[0])) {

                $groupId = $groupsFollowers[0];
                $invitation = $this->invitationModel->getByGroupFollowersId($groupId);
                if ($invitation['invitation']['available'] > 0) {
                    $this->invitationModel->setAvailableInvitations($invitation['invitation']['token'], 0);
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
                if (isset($data['messages']['value'])) {
                    return true;
                }
            }
        }

        return false;
    }
}