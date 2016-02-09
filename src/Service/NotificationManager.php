<?php

namespace Service;

use Model\Neo4j\GraphManager;

class NotificationManager
{

    /**
     * @var GraphManager
     */
    protected $graphManager;

    public function _construct(GraphManager $graphManager)
    {
        $this->graphManager = $graphManager;
    }

    /**
     * @param $from
     * @param $to
     * @return bool
     * @throws \Model\Neo4j\Neo4jException
     */
    public function areNotified($from, $to)
    {

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(from:User {qnoow_id: { from }})-[notified:NOTIFIED]-(to:User {qnoow_id: { to }})')
            ->setParameters(
                array(
                    'from' => (integer)$from,
                    'to' => (integer)$to,
                )
            )
            ->returns('from, to, notified');

        $result = $qb->getQuery()->getResultSet();

        return $result->count() > 0;
    }

    /**
     * @param $from
     * @param $to
     * @return bool
     * @throws \Model\Neo4j\Neo4jException
     */
    public function notify($from, $to)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(from:User {qnoow_id: { from }}), (to:User {qnoow_id: { to }})')
            ->setParameters(
                array(
                    'from' => (integer)$from,
                    'to' => (integer)$to,
                )
            )
            ->merge('(from)-[notified:NOTIFIED]-(to)')
            ->returns('from, to, notified');;

        $result = $qb->getQuery()->getResultSet();

        return $result->count() > 0;
    }
}