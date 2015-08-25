<?php

namespace Model\User;

use Everyman\Neo4j\Query\Row;
use Everyman\Neo4j\Relationship;
use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;

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

    }

    public function get($from, $to, $relation)
    {

    }

    public function create($from, $to, $relation, $data = array())
    {

        $this->validate($relation);

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(from:User)', '(to:User)')
            ->where('from.qnoow_id = { from }', 'to.qnoow_id = { to }')
            ->setParameter('from', (integer)$from)
            ->setParameter('to', (integer)$to)
            ->merge('(from)-[r:' . $relation . ']->(to)')
            ->set('r.timestamp = timestamp()');

        foreach ($data as $key => $value) {
            $qb->set("r.$key = { $key }")
                ->setParameter($key, $value);
        }

        $qb->returns('r');

        $result = $qb->getQuery()->getResultSet();

        $return = array();
        foreach ($result as $row) {

            /* @var $row Row */

            /* @var $relationship Relationship */
            $relationship = $row->offsetGet('r');

            $return[] = array_merge(array('id' => $relationship->getId()), $relationship->getProperties());

        }

        return $return;
    }

    public function remove($from, $to, $relation)
    {

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