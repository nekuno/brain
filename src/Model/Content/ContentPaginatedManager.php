<?php

namespace Model\Content;

use Everyman\Neo4j\Query\Row;
use Model\Link\LinkManager;

class ContentPaginatedManager extends AbstractContentPaginatedManager
{
    /**
     * Slices the query according to $offset, and $limit.
     * @param array $filters
     * @param int $offset
     * @param int $limit
     * @throws \Exception
     * @return array
     */
    public function slice(array $filters, $offset, $limit)
    {
        $qb = $this->gm->createQueryBuilder();
        $id = $filters['id'];
        $types = isset($filters['type']) ? $filters['type'] : array();

        $qb->match("(u:User)")
            ->where("u.qnoow_id = { userId }")
            ->match("(u)-[r:LIKES]->(content:Link {processed: 1})")
            ->where("NOT (content:LinkDisabled)");
        $qb->filterContentByType($types, 'content', array('u', 'r'));

        if (isset($filters['tag'])) {
            $qb->match('(content)-[:TAGGED]->(filterTag:Tag)')
                ->where('filterTag.name IN { filterTags } ');

            $params['filterTags'] = $filters['tag'];
        }

        $qb->optionalMatch("(content)-[:TAGGED]->(tag:Tag)")
            ->optionalMatch("(content)-[:SYNONYMOUS]->(synonymousLink:Link)")
            ->returns("id(content) as id, type(r) as rate, content, collect(distinct tag.name) as tags, labels(content) as types, COLLECT (DISTINCT synonymousLink) AS synonymous")
            ->orderBy("content.created DESC")
            ->skip("{ offset }")
            ->limit("{ limit }")
            ->setParameters(
                array(
                    'tag' => isset($filters['tag']) ? $filters['tag'] : null,
                    'userId' => (integer)$id,
                    'offset' => (integer)$offset,
                    'limit' => (integer)$limit,
                )
            );

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        $response = $this->buildResponse($result, $id);

        return $response;
    }

    /**
     * Counts the total results from queryset.
     * @param array $filters
     * @throws \Exception
     * @return int
     */
    public function countTotal(array $filters)
    {
        $id = $filters['id'];
        $types = isset($filters['type']) ? $filters['type'] : array();

        $qb = $this->gm->createQueryBuilder();
        $count = 0;

        $qb->match("(u:User)")
            ->where("u.qnoow_id = { userId }")
            ->match("(u)-[r:LIKES]->(content:Link {processed: 1})")
            ->where("NOT (content:LinkDisabled)");

        $qb->filterContentByType($types,'content', array('r'));

        if (isset($filters['tag'])) {
            $qb->match('(content)-[:TAGGED]->(filterTag:Tag)')
                ->where('filterTag.name IN { filterTags } ');

            $params['filterTags'] = $filters['tag'];
        }

        $qb->returns("count(r) as total")
            ->setParameters(
                array(
                    'tag' => isset($filters['tag']) ? $filters['tag'] : null,
                    'userId' => (integer)$id,
                )
            );

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        foreach ($result as $row) {
            $count = $row['total'];
        }

        return $count;
    }

    public function countAll($userId)
    {
        $types = LinkManager::getValidTypes();
        $qb = $this->gm->createQueryBuilder();
        $qb->match("(u:User {qnoow_id: { userId }})")
            ->setParameter('userId', $userId);
        $with = 'u,';
        foreach ($types as $type) {
            $qb->optionalMatch("(u)-[:LIKES]->(content$type:$type)")
                ->where('content' . $type . '.processed = 1 AND NOT (content' . $type . ':LinkDisabled)');
            $qb->with($with . "count(DISTINCT content$type) AS count$type");
            $with .= "count$type,";
        }

        $qb->returns(trim($with, ','));

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $totals = array();
        foreach ($result as $row) {
            foreach ($types as $type) {
                $totals[$type] = $row["count$type"];
            }
        }

        return $totals;
    }

    /**
     * @param $result
     * @param $id
     * @return array
     */
    protected function buildResponse($result, $id)
    {
        $response = array();
        foreach ($result as $row) {
            $content = new Interest();

            $content->setId($row['id']);
            $this->hydrateNodeProperties($content, $row);
            $this->hydrateSynonymous($content, $row);
            $this->hydrateTags($content, $row);
            $this->hydrateTypes($content, $row);
            $this->hydrateUserRates($content, $row, $id);

            $response[] = $content;
        }

        return $response;
    }

    protected function hydrateUserRates(Interest $content, Row $row, $id)
    {
        $user = array(
            'user' => array('id' => $id),
            'rate' => $row->offsetGet('rate'),
        );
        $content->addUserRate($user);
    }

}