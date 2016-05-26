<?php

namespace Model\User\Recommendation;

class ContentPopularRecommendationPaginatedModel extends AbstractContentPaginatedModel
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
        if ((integer)$limit == 0) {
            return array();
        }
        $return = array('items' => array());

        $types = isset($filters['type']) ? $filters['type'] : array();

        $params = array(
            'offset' => (integer)$offset,
            'limit' => (integer)$limit
        );

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(content:Link)')
            ->where('content.processed = 1');
        $qb->filterContentByType($types, 'content');

        if (isset($filters['tag'])) {
            $qb->match('(content)-[:TAGGED]->(filterTag:Tag)')
                ->where('filterTag.name IN { filterTags } ');

            $params['filterTags'] = $filters['tag'];
        }

        $qb->optionalMatch("(content)-[:SYNONYMOUS]->(synonymousLink:Link)")
            ->optionalMatch('(content)-[:TAGGED]->(tag:Tag)')
            ->returns(
                'id(content) as id',
                'content',
                'collect(distinct tag.name) as tags',
                'labels(content) as types',
                'COLLECT (DISTINCT synonymousLink) AS synonymous'
            )
            ->orderBy('1 - content.popularity ASC')
            ->skip('{ offset }')
            ->limit('{ limit }');

        $qb->setParameters($params);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $response = $this->buildResponseFromResult($result);
        $return['items'] = array_merge($return['items'], $response['items']);

        //Works with ContentPaginator (accepts $result), not Paginator (accepts $result['items'])
        return $response['items'];
    }
} 