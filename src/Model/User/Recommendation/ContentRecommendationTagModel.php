<?php

namespace Model\User\Recommendation;

use Model\Neo4j\GraphManager;

class ContentRecommendationTagModel
{

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @param GraphManager $gm
     */
    public function __construct(GraphManager $gm)
    {
        $this->gm = $gm;
    }

    /**
     * Get a list of recommended tag
     * @param $id
     * @param $startingWith
     * @param int $limit
     * @throws \Exception
     * @return array
     */
    public function getRecommendedTags($id, $startingWith = '', $limit = 0)
    {
        $response = array('items' => array());

        $params = array('UserId' => (integer)$id);

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(user:User {qnoow_id: {UserId}})-[affinity:AFFINITY]->(content:Link)')
            ->where('NOT (user)-[:LIKES|:DISLIKES]->(content) AND affinity.affinity > 0 AND content.processed = 1')
            ->match('(content)-[r:TAGGED]->(tag:Tag)');

        if ($startingWith != '') {
            $qb->where('tag.name =~ { tag }');
            $params['tag'] = '(?i)' . $startingWith . '.*';
        }

        $qb->returns('distinct tag.name as name, count(distinct r) as total')
            ->orderBy('tag.name');

        if ($limit != 0) {
            $qb->limit('{ limit }');
            $params['limit'] = (integer)$limit;
        }

        $qb->setParameters($params);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        foreach ($result as $row) {
            $content = array();
            $content['name'] = $row['name'];
            $content['count'] = $row['total'];

            $response['items'][] = $content;
        }

        return $response;
    }

    public function getAllTags($startingWith = '', $limit = 0)
    {
        $response = array('items' => array());
        $params = array();

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(tag:Tag)');

        if ($startingWith != '') {
            $qb->where('tag.name =~ { tag }');
            $params['tag'] = '(?i)' . $startingWith . '.*';
        }

        $qb->with('(tag)');

        $qb->optionalMatch('(tag)-[r:TAGGED]-(:Link)');

        $qb->returns('distinct tag.name as name, count(distinct r) as total')
            ->orderBy('tag.name');

        if ($limit != 0) {
            $qb->limit('{ limit }');
            $params['limit'] = (integer)$limit;
        }

        $qb->setParameters($params);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        foreach ($result as $row) {
            $content = array();
            $content['name'] = $row['name'];
            $content['count'] = $row['total'];

            $response['items'][] = $content;
        }

        return $response;
    }
} 