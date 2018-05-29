<?php

namespace Model\Relations;

use Event\UserBothLikedEvent;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\ResultSet;
use Everyman\Neo4j\Query\Row;
use Everyman\Neo4j\Relationship;
use Model\Exception\ErrorList;
use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;
use Model\Neo4j\QueryBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RelationsManager
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
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    public function __construct(GraphManager $gm, EventDispatcherInterface $dispatcher)
    {

        $this->gm = $gm;
        $this->dispatcher = $dispatcher;
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

    public function getAll($relation, $from = null, $to = null)
    {
        $this->validate($relation);

        $qb = $this->matchRelationshipQuery($relation, $from, $to);
        $this->returnRelationshipQuery($qb);

        $result = $qb->getQuery()->getResultSet();

        $return = $this->buildMany($result);

        return $return;
    }

    public function get($from, $to, $relation)
    {
        $this->validate($relation);

        $qb = $this->matchRelationshipQuery($relation, $from, $to);
        $this->returnRelationshipQuery($qb);

        $result = $qb->getQuery()->getResultSet();

        if ($result->count() === 0) {
            //throw new NotFoundHttpException(sprintf('There is no relation "%s" from user "%s" to "%s"', $relation, $from, $to));
            return array();
        }

        /* @var $row Row */
        $row = $result->current();

        return $this->buildOne($row);
    }

    public function countFrom($from, $relation)
    {
        return $this->count($relation, $from, null);
    }

    public function countTo($to, $relation)
    {
        return $this->count($relation, null, $to);
    }

    protected function count($relation, $from, $to)
    {
        $this->validate($relation);

        $qb = $this->matchRelationshipQuery($relation, $from, $to);
        $this->returnCountRelationshipsQuery($qb);

        $result = $qb->getQuery()->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        return $row->offsetGet('count');
    }

    public function create($from, $to, $relation, $data = array())
    {
        $this->validate($relation);

        if (!$this->relationMustBeCreated($from, $to, $relation)) {
            return array();
        }

        $qb = $this->mergeRelationshipQuery($relation, $from, $to);

        $this->setTimestampQuery($qb, $data);
        $this->setRelationshipAttributesQuery($qb, $data);

        $this->addExtraRelationshipsQuery($qb, $relation);
        $this->deleteExtraRelationshipsQuery($qb, $relation);

        $this->returnRelationshipQuery($qb);

        $result = $qb->getQuery()->getResultSet();

        if ($result->count() === 0) {
            throw new NotFoundHttpException(sprintf('Unable to create relation "%s" from user "%s" to "%s"', $relation, $from, $to));
        }

        if ($relation === self::LIKES && !empty($this->get($to, $from, self::LIKES))) {
            $this->dispatcher->dispatch(\AppEvents::USER_BOTH_LIKED, new UserBothLikedEvent($from, $to));
        }

        /* @var $row Row */
        $row = $result->current();

        return $this->buildOne($row);
    }

    public function remove($from, $to, $relation)
    {
        $this->validate($relation);
        $return = $this->get($from, $to, $relation);

        $qb = $this->matchRelationshipQuery($relation, $from, $to);
        $this->deleteExtraRelationshipsQuery($qb, $relation);
        $qb->delete('r');
        $qb->getQuery()->getResultSet();

        return $return;
    }

    public function getCanContactQuery($from = null, $to = null)
    {
        $qb = $this->matchRelationshipQuery(RelationsManager::BLOCKS, $from, $to);
        $this->returnRelationshipQuery($qb);

        return $qb;
    }

    protected function buildOne(Row $row)
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

    /**
     * @param $result
     * @return array
     */
    protected function buildMany(ResultSet $result)
    {
        $return = array();
        /* @var $row Row */
        foreach ($result as $row) {
            $return[] = $this->buildOne($row);
        }

        return $return;
    }

    protected function validate($relation)
    {
        $relations = self::getRelations();

        if (!in_array($relation, $relations)) {
            $message = sprintf('Relation type "%s" not allowed, possible values "%s"', $relation, implode('", "', $relations));
            $errorList = new ErrorList();
            $errorList->addError('type', $message);
            throw new ValidationException($errorList, $message);
        }
    }

    /**
     * @param $qb
     * @param $data
     */
    protected function setTimestampQuery(QueryBuilder $qb, &$data)
    {
        if (isset($data['timestamp'])) {
            $date = new \DateTime($data['timestamp']);
            $timestamp = ($date->getTimestamp()) * 1000;
            unset($data['timestamp']);
            $qb->set('r.timestamp = { timestamp }')
                ->setParameter('timestamp', $timestamp);
        } else {
            $qb->set('r.timestamp = timestamp()');
        }
        $qb->with('from', 'to', 'r');
    }

    /**
     * @param $qb
     * @param $data
     */
    protected function setRelationshipAttributesQuery(QueryBuilder $qb, $data)
    {
        foreach ($data as $key => $value) {
            $qb->set("r.$key = { $key }")
                ->setParameter($key, $value);
        }
        $qb->with('from', 'to', 'r');

    }

    /**
     * @param $qb
     * @param $relation
     */
    protected function deleteExtraRelationshipsQuery(QueryBuilder $qb, $relation)
    {
        $relationsToDelete = $this->getRelationsToDelete($relation);
        foreach ($relationsToDelete as $index => $relationToDelete) {
            $qb->optionalMatch('(from)-[rToDel' . $index . ':' . $relationToDelete . ']->(to)')
                ->delete('rToDel' . $index)
                ->with('from', 'to', 'r');
        }
    }

    /**
     * @param $qb
     * @param $relation
     */
    protected function addExtraRelationshipsQuery(QueryBuilder $qb, $relation)
    {
        $relationsToAdd = $this->getRelationsToAdd($relation);
        foreach ($relationsToAdd as $relationToAdd) {
            $qb->merge('(from)-[:' . $relationToAdd . ']->(to)');
        }
        $qb->with('from', 'to', 'r');
    }

    protected function relationMustBeCreated($from, $to, $relation)
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

    protected function getRelationsToDelete($relation)
    {
        switch ($relation) {
            case self::LIKES:
                return array(self::DISLIKES, self::IGNORES);
            case self::DISLIKES:
                return array(self::LIKES, self::IGNORES);
            case self::BLOCKS:
                return array(self::LIKES);
            default:
                break;
        }

        return array();
    }

    protected function getRelationsToAdd($relation)
    {
        switch ($relation) {
            case self::REPORTS:
                return array(self::BLOCKS);
            default:
                break;
        }

        return array();
    }

    protected function matchRelationshipQuery($relation, $from = null, $to = null)
    {
        return $this->initialRelationshipQuery($relation, $from, $to, false);
    }

    protected function mergeRelationshipQuery($relation, $from, $to)
    {
        return $this->initialRelationshipQuery($relation, $from, $to, true);
    }

    protected function initialRelationshipQuery($relation, $from, $to, $merge = false)
    {
        $userFrom = $from ? '(from:UserEnabled {qnoow_id: { from }})' : '(from:UserEnabled)';
        $userTo = $to ? '(to:UserEnabled {qnoow_id: { to }})' : '(to:UserEnabled)';

        $qb = $this->gm->createQueryBuilder();
        $relationship = '(from)-[r:' . $relation . ']->(to)';

        if ($merge){
            $qb->merge($userFrom);
            $qb->merge($userTo);
            $qb->merge($relationship);
        } else {
            $qb->match($userFrom, $userTo, $relationship);
        }

        if ($from) {
            $qb->setParameter('from', (integer)$from);
        }
        if ($to) {
            $qb->setParameter('to', (integer)$to);
        }

        $qb->with('from', 'to', 'r');

        return $qb;
    }

    protected function returnRelationshipQuery(QueryBuilder $qb)
    {
        $qb->returns('from', 'to', 'r');
    }

    protected function returnCountRelationshipsQuery(QueryBuilder $qb)
    {
        $qb->returns('count (r) AS count');
    }
}