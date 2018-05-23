<?php

namespace Model\User;

use Doctrine\ORM\EntityManagerInterface;
use Entity\UserTrackingEvent;
use Model\Neo4j\GraphManager;
use Service\EventDispatcher;
use Everyman\Neo4j\Query\Row;

class UserTrackingManager
{
    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    public function __construct(GraphManager $gm, EntityManagerInterface $em, EventDispatcher $dispatcher)
    {
        $this->gm = $gm;
        $this->em = $em;
        $this->dispatcher = $dispatcher;
    }

    public function getAll()
    {
        $userTrackingEvents = $this->em->getRepository('\Entity\UserTrackingEvent')->findAll();

        return $this->formatUserTrackingEventsArray($userTrackingEvents);
    }

    public function get($userId)
    {
        $userTrackingEvents = $this->em->getRepository('\Entity\UserTrackingEvent')->findBy(array('userId' => $userId), array('createdAt' => 'DESC'));

        return $this->formatUserTrackingEventsArray($userTrackingEvents);
    }

    public function set($userId, $action = null, $category = null, $tag = null, $trackingData = null)
    {
        /** @var UserTrackingEvent $userTrackingEvent */
        $userTrackingEvent = new UserTrackingEvent();
        $userTrackingEvent->setUserId($userId);
        $userTrackingEvent->setAction($action);
        $userTrackingEvent->setCategory($category);
        $userTrackingEvent->setTag($tag);
        $trackingData = json_decode($trackingData, true);
        $trackingData['request'] = array('IP' => $this->getInsecureIp());
        $userTrackingEvent->setData(json_encode($trackingData));

        $this->em->persist($userTrackingEvent);
        $this->em->flush();

        return $userTrackingEvent->toArray();
    }

    public function getUsersDataForCsv()
    {
        $qb = $this->gm->createQueryBuilder()
            ->match('(u:User)')
            ->where('NOT (u:GhostUser)')
            ->returns('u.qnoow_id AS id, u.username AS username');
        $query = $qb->getQuery();
        $result = $query->getResultSet();
        $return = array();
        if ($result->count() > 0) {
            /* @var $row Row */
            foreach ($result as $row) {
                $id = $row->offsetGet('id');
                $username = $row->offsetGet('username');
                $return[] = array('id' => $id, 'username' => $username);
            }
        }

        return $return;
    }

    protected function formatUserTrackingEventsArray(array $userTrackingEvents)
    {
        $userTrackingEventsArray = array();
        /** @var UserTrackingEvent $userTrackingEvent */
        foreach ($userTrackingEvents as $userTrackingEvent) {
            $username = $this->getUsername($userTrackingEvent->getUserId());
            $userTrackingEventsArray[] = $userTrackingEvent->toArray() + array('username' => $username);
        }

        return $userTrackingEventsArray;
    }

    protected function getUsername($userId)
    {
        $qb = $this->gm->createQueryBuilder()
            ->match('(u:User {qnoow_id: { userId }})')
            ->setParameter('userId', $userId)
            ->returns('u.username AS username');
        $query = $qb->getQuery();
        $result = $query->getResultSet();
        if ($result->count() > 0) {
            /* @var $row Row */
            $row = $result->current();
            $username = $row->offsetGet('username');

            return $username;
        }

        return 'No username';
    }

    protected function getInsecureIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = null;
        }

        return !filter_var($ip, FILTER_VALIDATE_IP) === false ? $ip : "";
    }
}