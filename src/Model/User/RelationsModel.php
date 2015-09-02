<?php

namespace Model\User;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Everyman\Neo4j\Relationship;
use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RelationsModel
{

    const BLOCKS = 'BLOCKS';
    const FAVORITES = 'FAVORITES';
    const LIKES = 'LIKES';
    const REPORTS = 'REPORTS';

    /**
     * @var GraphManager
     */
    protected $gm;

    public function __construct(GraphManager $gm)
    {

        $this->gm = $gm;
    }

    public function getAll($from, $relation)
    {

        $this->validate($relation);

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(from:User)-[r:' . $relation . ']-(to:User)')
            ->where('from.qnoow_id = { from }')
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

        $qb->match('(from:User)-[r:' . $relation . ']-(to:User)')
            ->where('from.qnoow_id = { from }', 'to.qnoow_id = { to }')
            ->setParameter('from', (integer)$from)
            ->setParameter('to', (integer)$to)
            ->returns('from', 'to', 'r');

        $result = $qb->getQuery()->getResultSet();

        if ($result->count() === 0) {
            throw new NotFoundHttpException(sprintf('There is no relation "%s" from user "%s" to "%s"', $relation, $from, $to));
        }

        /* @var $row Row */
        $row = $result->current();

        return $this->build($row);
    }

    public function create($from, $to, $relation, $data = array())
    {

        $this->validate($relation);

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(from:User)', '(to:User)')
            ->where('from.qnoow_id = { from }', 'to.qnoow_id = { to }')
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

        $qb->returns('from', 'to', 'r');

        $result = $qb->getQuery()->getResultSet();

        if ($result->count() === 0) {
            throw new NotFoundHttpException(sprintf('Unable to create relation "%s" from user "%s" to "%s"', $relation, $from, $to));
        }

        /* @var $row Row */
        $row = $result->current();

        return $this->build($row);

    }

    public function remove($from, $to, $relation)
    {
        $this->validate($relation);

        $return = $this->get($from, $to, $relation);

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(from:User)-[r:' . $relation . ']-(to:User)')
            ->where('from.qnoow_id = { from }', 'to.qnoow_id = { to }')
            ->setParameter('from', (integer)$from)
            ->setParameter('to', (integer)$to)
            ->delete('r');

        $qb->getQuery()->getResultSet();

        return $return;

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

        $relations = array(
            self::BLOCKS,
            self::FAVORITES,
            self::LIKES,
            self::REPORTS,
        );

        if (!in_array($relation, $relations)) {
            throw new ValidationException(sprintf('Relation type "%s" not allowed, possible values "%s"', $relation, implode('", "', $relations)));
        }
    }

}