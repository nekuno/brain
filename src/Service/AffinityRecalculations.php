<?php

namespace Service;

use Event\AffinityProcessEvent;
use Event\AffinityProcessStepEvent;
use Entity\EmailNotification;
use Model\Link\Link;
use Model\Link\LinkManager;
use Model\Neo4j\GraphManager;
use Model\User\User;
use Model\Affinity\AffinityManager;
use Model\User\UserManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

class AffinityRecalculations
{

    /* @var EventDispatcherInterface */
    protected $dispatcher;

    /* @var $emailNotifications EmailNotifications */
    protected $emailNotifications;

    /* @var $translator TranslatorInterface */
    protected $translator;

    /* @var $graphManager GraphManager */
    protected $graphManager;

    /* @var $linkModel LinkManager */
    protected $linkModel;

    /* @var $userManager UserManager */
    protected $userManager;

    /* @var $affinityModel AffinityManager */
    protected $affinityModel;

    protected $linksToEmail = 3;

    const MIN_AFFINITY = 0.7;

    public function __construct(EventDispatcherInterface $dispatcher, EmailNotifications $emailNotifications, TranslatorInterface $translator, GraphManager $graphManager, LinkManager $linkModel, UserManager $userManager, AffinityManager $affinityModel)
    {
        $this->dispatcher = $dispatcher;
        $this->emailNotifications = $emailNotifications;
        $this->translator = $translator;
        $this->graphManager = $graphManager;
        $this->linkModel = $linkModel;
        $this->userManager = $userManager;
        $this->affinityModel = $affinityModel;
    }

    /**
     * Predict best content for an user and calculate affinity between them
     * If that content already has affinity to the user, recalculate it.
     * Notifies the user by email if the affinity is very high.
     *
     * @param null $userId
     * @param int $limitContent
     * @param int $limitUsers
     * @param int $notifyLimit
     * @param int $seconds Affinities (re)calculated before this value are untouched, null to default
     * @return array
     * @throws \Exception
     */
    public function recalculateAffinities($userId, $limitContent = 40, $limitUsers = 20, $notifyLimit = 99999, $seconds = null)
    {
        $user = $this->userManager->getById((integer)$userId, true);

        $processId = time();
        $affinityProcessEvent = new AffinityProcessEvent($userId, $processId);
        $this->dispatcher->dispatch(\AppEvents::AFFINITY_PROCESS_START, $affinityProcessEvent);

        $filters = array('affinity' => true);
        $links = $this->linkModel->getPredictedContentForAUser($userId, (integer)$limitContent, (integer)$limitUsers, $filters);

        $affinities = array();
        $linksToEmail = array();
        $result = array();
        $counterNotified = 0;
        $count = count($links);
        $prevPercentage = 0;
        foreach ($links as $index => $link) {

            $linkId = $link->getId();
            $affinity = $this->affinityModel->getAffinity($userId, $linkId, $seconds);

            $percentage = round(($index + 1) / $count * 100);
            if ($percentage > $prevPercentage) {
                $affinityProcessStepEvent = new AffinityProcessStepEvent($userId, $processId, $percentage);
                $this->dispatcher->dispatch(\AppEvents::AFFINITY_PROCESS_STEP, $affinityProcessStepEvent);
                $prevPercentage = $percentage;
            }

            if ($affinity->getAffinity() < $this::MIN_AFFINITY) {
                continue;
            }
            $affinities[$linkId] = $affinity->getAffinity();
            if ($affinity->getAffinity() > $notifyLimit) {
                $whenNotified = $this->linkModel->getWhenNotified($userId, $linkId);
                if ($whenNotified !== null) {
                    continue;
                }
                $linksToEmail[] = $link;
                if ($counterNotified < $this->linksToEmail) {
                    $this->linkModel->setLinkNotified($userId, $linkId);
                    $counterNotified++;
                }
            }
        }

        $this->dispatcher->dispatch(\AppEvents::AFFINITY_PROCESS_FINISH, $affinityProcessEvent);

        try {
            if (!empty($linksToEmail)) {
                $result['emailInfo'] = $this->sendEmail($linksToEmail, $user);
            }
        } catch (\Exception $ex) {
            /** @var Link $link */
            foreach ($linksToEmail as $link) {
                $this->linkModel->unsetLinkNotified($userId, $link->getId());
            }
            throw $ex;
        }
        $result['affinities'] = $affinities;

        return $result;
    }

    /**
     * @param array $links
     * @param User $user
     * @return array
     * @throws \Exception
     */
    protected function sendEmail(array $links, User $user)
    {
        $emailInfo = $this->saveInfo($links, $user->getUsername());

        $recipients = $this->emailNotifications->send(
            EmailNotification::create()
                ->setType(EmailNotification::EXCEPTIONAL_LINKS)
                ->setSubject($this->translator->trans('notifications.messages.exceptional_links.subject'))
                ->setUserId($user->getId())
                ->setRecipient($user->getEmail())
                ->setInfo($emailInfo)
        );

        $emailInfo['recipients'] = $recipients;

        return $emailInfo;
    }

    public function estimateTime($count)
    {
        $estimatedTime = 10 + 1 * (pow(10, -7)) * (pow($count, 2)); //TODO: Improve formula with more data at low users
        return $estimatedTime;
    }

    /**
     * @param array $links Ordered by prediction
     * @param $username
     * @return array
     */
    protected function saveInfo(array $links, $username)
    {
        $info = array();
        $info['totalCount'] = count($links);
        $amount = min($this->linksToEmail, count($links));
        for ($i = 0; $i < $amount; $i++) {
            $info['links'][] = $links[$i];
        }
        $info['username'] = $username;

        return $info;
    }

}