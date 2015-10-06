<?php
/**
 * Created by: yawmoght
 */

namespace Service;

use Model\Entity\EmailNotification;
use Model\LinkModel;
use Model\Neo4j\GraphManager;
use Model\User\Affinity\AffinityModel;
use Model\UserModel;
use Silex\Translator;

class AffinityRecalculations
{

    /* @var $graphManager GraphManager */
    protected $graphManager;

    /* @var $linkModel LinkModel */
    protected $linkModel;

    /* @var $userModel UserModel */
    protected $userModel;

    /* @var $affinityModel AffinityModel */
    protected $affinityModel;

    /* @var $emailNotifications EmailNotifications */
    protected $emailNotifications;

    /* @var $translator Translator */
    protected $translator;

    protected $linksToEmail = 3;

    const MIN_AFFINITY = 0.7;

    function __construct($emailNotifications, $translator, $graphManager, $linkModel, $userModel, $affinityModel)
    {
        $this->graphManager = $graphManager;
        $this->linkModel = $linkModel;
        $this->userModel = $userModel;
        $this->affinityModel = $affinityModel;
        $this->emailNotifications = $emailNotifications;
        $this->translator = $translator;
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
        $user = $this->userModel->getById((integer)$userId);

        $filters = array('affinity' => true);
        $links = $this->linkModel->getPredictedContentForAUser($userId, (integer)$limitContent, (integer)$limitUsers, $filters);

        $affinities = array();
        $linksToEmail = array();
        $result = array();
        $counterNotified = 0;
        foreach ($links as $link) {
            $affinity = $this->affinityModel->getAffinity($userId, $link['id'], $seconds);
            if ($affinity['affinity'] < $this::MIN_AFFINITY){
                continue;
            }
            $affinities[$link['id']] = $affinity['affinity'];
            if ($affinity['affinity'] > $notifyLimit) {
                $whenNotified = $this->linkModel->getWhenNotified($userId, $link['id']);
                if ($whenNotified !== null) {
                    continue;
                }
                $linksToEmail[] = $link;
                if ($counterNotified < $this->linksToEmail) {
                    $this->linkModel->setLinkNotified($userId, $link['id']);
                    $counterNotified++;
                }
            }
        }

        try {
            if (!empty($linksToEmail)) {
                $result['emailInfo'] = $this->sendEmail($linksToEmail, $user);
            }
        } catch (\Exception $ex) {
            foreach ($linksToEmail as $link) {
                $this->linkModel->unsetLinkNotified($userId, $link['id']);
            }
            throw $ex;
        }
        $result['affinities'] = $affinities;
        return $result;
    }

    /**
     * @param array $links
     * @param array $user
     * @return array
     * @throws \Exception
     */
    protected function sendEmail(array $links, array $user)
    {
        $emailInfo = $this->saveInfo($links, $user['username']);

        $recipients = $this->emailNotifications->send(
            EmailNotification::create()
                ->setType(EmailNotification::EXCEPTIONAL_LINKS)
                ->setSubject($this->translator->trans('notifications.messages.exceptional_links.subject'))
                ->setUserId($user['qnoow_id'])
                ->setRecipient($user['email'])
                ->setInfo($emailInfo));

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