<?php

namespace Model\Content;

use Model\Neo4j\GraphManager;

class ContentTagManager
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
    public function getContentTags($id, $startingWith = '', $limit = 0)
    {
        $response = array('items' => array());

        $params = array('userId' => (integer)$id);

        if ($limit != 0) {
            $params['limit'] = (integer)$limit;
        }

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(u:User)')
            ->where('u.qnoow_id = {userId}')
            ->match('(u)-[:LIKES]->(content:Link)')
            ->where('content.processed = 1')
            ->match('(content)-[r:TAGGED]->(tag:Tag)');
        if ($startingWith != '') {
            $params['tag'] = '(?i)' . $startingWith . '.*';
            $qb->where('tag.name =~ {tag}');
        }

        $qb->returns('tag.name as name', 'count(distinct r) as total');
        $qb->orderBy('tag.name');
        if (array_key_exists('limit', $params)) {
            $qb->limit('{limit}');
        };
        $qb->setParameters($params);

        try {
            $query = $qb->getQuery();
            $result = $query->getResultSet();

            foreach ($result as $row) {
                $content = array();
                $content['name'] = $row['name'];
                $content['count'] = $row['total'];

                $response['items'][] = $content;
            }

        } catch (\Exception $e) {
            throw $e;
        }

        return $response;
    }
} 