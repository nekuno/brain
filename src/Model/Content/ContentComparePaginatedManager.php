<?php

namespace Model\Content;

use Everyman\Neo4j\Query\Row;
use Model\Link\LinkManager;

class ContentComparePaginatedManager extends AbstractContentPaginatedManager
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
        $id = $filters['id'];
        $id2 = $filters['id2'];
        $types = isset($filters['type']) ? $filters['type'] : array();

        $qb = $this->gm->createQueryBuilder();

        $showOnlyCommon = false;
        if (isset($filters['showOnlyCommon'])) {
            $showOnlyCommon = $filters['showOnlyCommon'];
        }

        $qb->match("(u:User), (u2:User)")
            ->where("u.qnoow_id = { userId }","u2.qnoow_id = { userId2 }")
            ->match("(u)-[r:LIKES]->(content:Link {processed: 1})")
            ->where("NOT (u2)-[:REPORTS]->(content) AND NOT (content:LinkDisabled)");
        $qb->filterContentByType($types, 'content', array('u2', 'r'));

        if (isset($filters['tag'])) {
            $names = json_encode($filters['tag']);
            $qb->match('(content)-[:TAGGED]->(filterTag:Tag)')
                ->where('filterTag.name IN { filterTags } ');

            $params['filterTags'] = $names;
        }
        if ($showOnlyCommon) {
            $qb->match("(u2)-[r2:LIKES]->(content)");
        } else {
            $qb->optionalMatch("(u2)-[r2:LIKES]->(content)");
        }

        $qb->optionalMatch("(content)-[:TAGGED]->(tag:Tag)")
            ->optionalMatch("(u2)-[a:AFFINITY]->(content)")
            ->optionalMatch("(content)-[:SYNONYMOUS]->(synonymousLink:Link)")
            ->returns("id(content) as id,  type(r) as rate1, type(r2) as rate2, content, a.affinity as affinity, collect(distinct tag.name) as tags, labels(content) as types, COLLECT (DISTINCT synonymousLink) AS synonymous")
            ->skip("{ offset }")
            ->limit("{ limit }")
            ->setParameters(
                array(
                    'userId' => $id,
                    'userId2' => $id2,
                    'tag' => isset($filters['tag']) ? $filters['tag'] : null,
                    'offset' => (integer)$offset,
                    'limit' => (integer)$limit,
                )
            );

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        //TODO: Build with linkModel
        $response = $this->buildResponse($result, $id, $id2);

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
        $id2 = isset($filters['id2']) ? (integer)$filters['id2'] : null;
        $types = isset($filters['type']) ? $filters['type'] : array();

        $count = 0;
        $qb = $this->gm->createQueryBuilder();

        $showOnlyCommon = false;
        if (isset($filters['showOnlyCommon'])) {
            $showOnlyCommon = $filters['showOnlyCommon'];
        }

        $qb->match("(u:User)","(u2:User)")
            ->where("u.qnoow_id = { userId }", "u2.qnoow_id = { userId2 }")
            ->match("(u)-[r:LIKES]->(content:Link {processed: 1})")
            ->where("NOT (u2)-[:REPORTS]->(content) AND NOT (content:LinkDisabled)");
        $qb->filterContentByType($types, 'content', array('u2', 'r'));

        if ($showOnlyCommon) {
            $qb->match("(u2)-[r2:LIKES]->(content)");
        }

        if (isset($filters['tag'])) {
            $qb->match('(content)-[:TAGGED]->(filterTag:Tag)')
                ->where('filterTag.name IN { filterTags } ');

            $params['filterTags'] = $filters['tag'];
        }

        $qb->returns("count(r) as total")
            ->setParameters(
                array(
                    'userId' => (integer)$id,
                    'userId2' => $id2,
                    'tag' => isset($filters['tag']) ? $filters['tag'] : null,

                )
            );

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        foreach ($result as $row) {
            $count = $row['total'];
        }

        return $count;
    }

    /**
     * @param $userId
     * @param $ownUserId
     * @param bool $showOnlyCommon
     * @return array
     */
    //TODO: Merge with countTotal
    public function countAll($userId, $ownUserId, $showOnlyCommon = false)
    {
        $types = LinkManager::getValidTypes();
        $qb = $this->gm->createQueryBuilder();
        $qb->match("(u:User {qnoow_id: { userId }}), (ownU:User {qnoow_id: { ownUserId }})")
            ->setParameters(array(
                'userId' => $userId,
                'ownUserId' => $ownUserId,
            ));
        $with = 'u, ownU,';

        foreach ($types as $type) {
            $qb->optionalMatch("(u)-[:LIKES]->(content$type:$type {processed: 1})");
            if ($showOnlyCommon) {
                $qb->where("(ownU)-[:LIKES]->(content$type) AND NOT (ownU)-[:REPORTS]->(content$type) AND NOT (content$type:LinkDisabled)");
            } else {
                $qb->where("NOT (ownU)-[:REPORTS]->(content$type) AND NOT (content$type:LinkDisabled)");
            }
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
     * @param $id2
     * @return array
     */
    protected function buildResponse($result, $id, $id2)
    {
        $response = array();
        foreach ($result as $row) {
            $content = new ComparedInterest();

            $content->setId($row['id']);
            $this->hydrateNodeProperties($content, $row);
            $this->hydrateSynonymous($content, $row);
            $this->hydrateTags($content, $row);
            $this->hydrateTypes($content, $row);
            $this->hydrateUserRates($content, $row, $id, $id2);
            $this->hydrateMatch($content, $row);

            $response[] = $content;
        }

        return $response;
    }

    protected function hydrateUserRates(Interest $content, Row $row, $id, $id2 = null)
    {
        $user = array(
            'user' => array('id' => $id),
            'rate' => $row->offsetGet('rate1'),
        );
        $content->addUserRate($user);

        if ($row->offsetExists('rate2') && $row->offsetGet('rate2')){
            $user2 = array(
                'user' => array('id' => $id2),
                'rate' => $row->offsetGet('rate2'),
            );
            $content->addUserRate($user2);
        }
    }

    protected function hydrateMatch(ComparedInterest $content, Row $row)
    {
        $content->setMatch($row['affinity']);
    }
}