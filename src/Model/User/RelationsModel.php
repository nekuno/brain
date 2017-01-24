<?php

namespace Model\User;

use Doctrine\DBAL\Connection;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Everyman\Neo4j\Relationship;
use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;
use Manager\UserManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RelationsModel
{

    const BLOCKS = 'BLOCKS';
    const FAVORITES = 'FAVORITES';
    const LIKES = 'LIKES';
    const DISLIKES = 'DISLIKES';
    const IGNORES = 'IGNORES';
    const REPORTS = 'REPORTS';

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var Connection
     */
    protected $connectionBrain;

    /**
     * @var UserManager
     */
    protected $userManager;

    public function __construct(GraphManager $gm, Connection $connectionBrain, UserManager $userManager)
    {

        $this->gm = $gm;
        $this->connectionBrain = $connectionBrain;
        $this->userManager = $userManager;
    }

    static public function getRelations()
    {
        return array(
            self::BLOCKS,
            self::FAVORITES,
            self::LIKES,
            self::DISLIKES,
            self::IGNORES,
            self::REPORTS,
        );
    }

    public function getAll($from, $relation)
    {

        $this->validate($relation);

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(from:User {qnoow_id: { from }})-[r:' . $relation . ']->(to:User)')
            ->setParameter('from', (integer)$from)
            ->returns('from', 'to', 'r');

        $result = $qb->getQuery()->getResultSet();

        $return = array();
        /* @var $row Row */
        foreach ($result as $row) {
            $return[] = $this->build($row);
        }

        return $return;
    }

    public function get($from, $to, $relation)
    {

        $this->validate($relation);

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(from:User {qnoow_id: { from }})-[r:' . $relation . ']->(to:User {qnoow_id: { to }})')
            ->setParameter('from', (integer)$from)
            ->setParameter('to', (integer)$to)
            ->returns('from', 'to', 'r');

        $result = $qb->getQuery()->getResultSet();

        if ($result->count() === 0) {
            //throw new NotFoundHttpException(sprintf('There is no relation "%s" from user "%s" to "%s"', $relation, $from, $to));
            return array();
        }

        /* @var $row Row */
        $row = $result->current();

        return $this->build($row);
    }

    public function countFrom($from, $relation)
    {
        $this->validate($relation);

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(from:User {qnoow_id: { from }})-[r:' . $relation . ']->(to:User)')
            ->setParameter('from', (integer)$from)
            ->returns('COUNT(r) as count');

        $result = $qb->getQuery()->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        return $row->offsetGet('count');
    }

    public function countTo($to, $relation)
    {
        $this->validate($relation);

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(from:User)-[r:' . $relation . ']->(to:User {qnoow_id: { to }})')
            ->setParameter('to', (integer)$to)
            ->returns('COUNT(r) as count');

        $result = $qb->getQuery()->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        return $row->offsetGet('count');
    }

    public function create($from, $to, $relation, $data = array())
    {

        $this->validate($relation);

        if ($this->relationMustBeCreated($from, $to, $relation)) {
            $relationsToDelete = $this->getRelationsToDelete($relation);

            $qb = $this->gm->createQueryBuilder();

            $qb->match('(from:User {qnoow_id: { from }})', '(to:User {qnoow_id: { to }})')
                ->setParameter('from', (integer)$from)
                ->setParameter('to', (integer)$to)
                ->merge('(from)-[r:' . $relation . ']->(to)');

            if (isset($data['timestamp'])) {
                $date = new \DateTime($data['timestamp']);
                $timestamp = ($date->getTimestamp()) * 1000;
                unset($data['timestamp']);
                $qb->set('r.timestamp = { timestamp }')
                    ->setParameter('timestamp', $timestamp);
            } else {
                $qb->set('r.timestamp = timestamp()');
            }

            foreach ($data as $key => $value) {
                $qb->set("r.$key = { $key }")
                    ->setParameter($key, $value);
            }
            $qb->with('from', 'to', 'r');
            foreach ($relationsToDelete as $index => $relation) {
                $qb->optionalMatch('(from)-[rToDel' . $index . ':' . $relation . ']->(to)')
                    ->delete('rToDel' . $index)
                    ->with('from', 'to', 'r');
            }
            $qb->returns('from', 'to', 'r');

            $result = $qb->getQuery()->getResultSet();

            if ($result->count() === 0) {
                throw new NotFoundHttpException(sprintf('Unable to create relation "%s" from user "%s" to "%s"', $relation, $from, $to));
            }

            /* @var $row Row */
            $row = $result->current();

            return $this->build($row);
        }

        return array();
    }

    public function remove($from, $to, $relation)
    {
        $this->validate($relation);
        $relationsToDelete = $this->getRelationsToDelete($relation);
        $relationsToDelete[] = $relation;
        $relationsString = implode($relationsToDelete, '|');
        $return = $this->get($from, $to, $relation);

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(from:User {qnoow_id: { from }})-[r:' . $relationsString . ']-(to:User {qnoow_id: { to }})')
            ->setParameter('from', (integer)$from)
            ->setParameter('to', (integer)$to)
            ->delete('r')
            ->with('from', 'to');

        $qb->returns('from', 'to');
        $qb->getQuery()->getResultSet();

        return $return;

    }

    public function contactFrom($id)
    {

        $messaged = $this->getMessaged($id);

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(from:User {qnoow_id: { id }})', '(to:User)')
            ->where('to.qnoow_id <> { id }')
            ->optionalMatch('(from)-[fav:FAVORITES]->(to)')
            ->setParameter('id', (integer)$id)
            ->with('from', 'to', 'fav')
            ->where('to.qnoow_id IN { messaged } OR NOT fav IS NULL')
            ->setParameter('messaged', $messaged)
            ->with('from', 'to')
            ->where('NOT (from)-[:' . RelationsModel::BLOCKS . ']-(to)')
            ->returns('to AS u')
            ->orderBy('u.qnoow_id');

        $result = $qb->getQuery()->getResultSet();
        $return = array();

        foreach ($result as $row) {
            /* @var $row Row */
            $return[] = $this->userManager->build($row);
        }

        return $return;
    }

    public function contactTo($id)
    {

        $messaged = $this->getMessaged($id);

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(from:User {qnoow_id: { id }})', '(to:User)')
            ->where('to.qnoow_id <> { id }')
            ->optionalMatch('(from)<-[fav:FAVORITES]-(to)')
            ->setParameter('id', (integer)$id)
            ->with('from', 'to', 'fav')
            ->where('to.qnoow_id IN { messaged } OR NOT fav IS NULL')
            ->setParameter('messaged', $messaged)
            ->with('from', 'to')
            ->where('NOT (from)-[:' . RelationsModel::BLOCKS . ']-(to)')
            ->returns('to AS u')
            ->orderBy('u.qnoow_id');

        $result = $qb->getQuery()->getResultSet();
        $return = array();

        foreach ($result as $row) {
            /* @var $row Row */
            $return[] = $this->userManager->build($row);
        }

        return $return;
    }

    public function contact($from, $to)
    {

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(from:User {qnoow_id: { from }})-[r:' . RelationsModel::BLOCKS . ']-(to:User {qnoow_id: { to }})')
            ->setParameter('from', (integer)$from)
            ->setParameter('to', (integer)$to)
            ->returns('from', 'to', 'r');

        $result = $qb->getQuery()->getResultSet();

        return $result->count() === 0;
    }

    protected function getMessaged($id)
    {

        $messaged = $this->connectionBrain->executeQuery(
            '
            SELECT * FROM (
              SELECT user_to AS user FROM chat_message
              WHERE user_from = :id
              GROUP BY user_to
              UNION
              SELECT user_from AS user FROM chat_message
              WHERE user_to = :id
              GROUP BY user_from
            ) AS tmp ORDER BY tmp.user',
            array('id' => (integer)$id)
        )->fetchAll(\PDO::FETCH_COLUMN);

        $messaged = array_map(
            function ($i) {
                return (integer)$i;
            },
            $messaged
        );

        return $messaged;
    }

    protected function build(Row $row)
    {
        /* @var $from Node */
        $from = $row->offsetGet('from');
        /* @var $to Node */
        $to = $row->offsetGet('to');
        /* @var $relationship Relationship */
        $relationship = $row->offsetGet('r');

        return array_merge(
            array(
                'id' => $relationship->getId(),
                'from' => $from->getProperties(),
                'to' => $to->getProperties(),
            ),
            $relationship->getProperties()
        );
    }

    protected function validate($relation)
    {

        $relations = self::getRelations();

        if (!in_array($relation, $relations)) {
            throw new ValidationException(array(), sprintf('Relation type "%s" not allowed, possible values "%s"', $relation, implode('", "', $relations)));
        }
    }

    private function relationMustBeCreated($from, $to, $relation)
    {
        if ($relation === self::IGNORES) {
            $likes = $this->get($from, $to, self::LIKES);
            $dislikes = $this->get($from, $to, self::DISLIKES);
            if (count($likes) + count($dislikes) > 0) {
                return false;
            }
        }

        return true;
    }

    private function getRelationsToDelete($relation)
    {
        switch ($relation) {
            case self::LIKES:
                return array(self::DISLIKES, self::IGNORES);
            case self::DISLIKES:
                return array(self::LIKES, self::IGNORES);
        }

        return array();
    }
}