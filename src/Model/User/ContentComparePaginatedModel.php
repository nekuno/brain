<?php

namespace Model\User;

use Paginator\PaginatedInterface;
use Model\Neo4j\GraphManager;

class ContentComparePaginatedModel implements PaginatedInterface
{
    /**
     * @var array
     */
    private static $validTypes = array('Audio', 'Video', 'Image');

    public function __construct(GraphManager $gm)
    {
        $this->gm = $gm;
    }

    public function getValidTypes()
    {
        return self::$validTypes;
    }

    /**
     * Hook point for validating the query.
     * @param array $filters
     * @return boolean
     */
    public function validateFilters(array $filters)
    {
        $hasIds = isset($filters['id']) && isset($filters['id2']);

        if (isset($filters['type'])) {
            $hasValidType = in_array($filters['type'], $this->getValidTypes());
        } else {
            $hasValidType = true;
        }

        $isValid = $hasIds && $hasValidType;

        return $isValid;
    }

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
        $response = array();
        $id = $filters['id'];
        $id2 = $filters['id2'];
        $qb = $this->gm->createQueryBuilder();

        $showOnlyCommon = false;
        if (isset($filters['showOnlyCommon'])) {
            $showOnlyCommon = $filters['showOnlyCommon'];
        }

        $linkType = 'Link';
        if (isset($filters['type'])) {
            $linkType = $filters['type'];
        }

        $qb->match("(u:User), (u2:User)")
            ->where("u.qnoow_id = { userId } AND u2.qnoow_id = { userId2 }")
            ->match("(u)-[r:LIKES|DISLIKES]->(content:" . $linkType . ")")
            ->optionalMatch("(content)-[:TAGGED]->(tag:Tag)");

        if (isset($filters['tag'])) {
            $qb->match("(content)-[:TAGGED]->(filterTag:Tag)")
                ->where("filterTag.name = { tag }");
        }
        if ($showOnlyCommon) {
            $qb->match("(u2)-[r2:LIKES|DISLIKES]->(content)");
        } else {
            $qb->optionalMatch("(u2)-[r2:LIKES|DISLIKES]->(content)");
        }

        $qb->optionalMatch("(u2)-[a:AFFINITY]->(content)")
            ->optionalMatch("(content)-[:SYNONYMOUS]->(synonymousLink:Link)")
            ->returns("id(content) as id,  type(r) as rate1, type(r2) as rate2, content, a.affinity as affinity, collect(distinct tag.name) as tags, labels(content) as types, synonymousLink AS synonymous")
            ->skip("{ offset }")
            ->limit("{ limit }")
            ->setParameters(array(
                'userId' => $id,
                'userId2' => $id2,
                'tag' => isset($filters['tag']) ? $filters['tag'] : null,
                'offset' => (integer)$offset,
                'limit' => (integer)$limit,
            ));

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        foreach ($result as $row) {
            $content = array();

            $content['id'] = $row['id'];
            $content['url'] = $row['content']->getProperty('url');
            $content['title'] = $row['content']->getProperty('title');
            $content['description'] = $row['content']->getProperty('description');
            $content['thumbnail'] = $row['content']->getProperty('thumbnail');
            $content['synonymous'] = array();

            if(isset($row['synonymous'])) {
                foreach ($row['synonymous'] as $synonymousLink) {
                    $synonymous = array();
                    $synonymous['id'] = $synonymousLink->getProperty('id');
                    $synonymous['url'] = $synonymousLink->getProperty('url');
                    $synonymous['title'] = $synonymousLink->getProperty('title');
                    $synonymous['thumbnail'] = $synonymousLink->getProperty('thumbnail');

                    $content['synonymous'][] = $synonymous;
                }
            }

            foreach ($row['tags'] as $tag) {
                $content['tags'][] = $tag;
            }

            foreach ($row['types'] as $type) {
                $content['types'][] = $type;
            }

            $user1 = array();
            $user1['user']['id'] = $id;
            $user1['rate'] = $row['rate1'];
            $content['user_rates'][] = $user1;

            if (null != $row['rate2']) {
                $user2 = array();
                $user2['user']['id'] = $id2;
                $user2['rate'] = $row['rate2'];
                $content['user_rates'][] = $user2;
            }

            if ($row['content']->getProperty('embed_type')) {
                $content['embed']['type'] = $row['content']->getProperty('embed_type');
                $content['embed']['id'] = $row['content']->getProperty('embed_id');
            }

            $content['match'] = $row['affinity'];

            $response[] = $content;
        }

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
        $count = 0;
        $qb = $this->gm->createQueryBuilder();

        $showOnlyCommon = false;
        if (isset($filters['showOnlyCommon'])) {
            $showOnlyCommon = $filters['showOnlyCommon'];
        }

        $linkType = 'Link';
        if (isset($filters['type'])) {
            $linkType = $filters['type'];
        }

        $qb->match("(u:User)")
            ->where("u.qnoow_id = { userId }")
            ->match("(u)-[r:LIKES|DISLIKES]->(content:" . $linkType . ")");

        if ($showOnlyCommon) {
            $qb->match("(u2:User)-[:LIKES|DISLIKES]->(content)")
                ->where("u2.qnoow_id = { userId2 }");
        }
        if (isset($filters['tag'])) {
            $qb->match("(content)-[:TAGGED]->(filterTag:Tag)")
                ->where("filterTag.name = { tag }");

            $params['tag'] = $filters['tag'];
        }

        $qb->returns("count(r) as total")
            ->setParameters(array(
                'userId' => (integer)$id,
                'userId2' => isset($filters['id2']) ? (integer)$filters['id2'] : null,
                'tag' => isset($filters['tag']) ? $filters['tag'] : null,


            ));

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        foreach ($result as $row) {
            $count = $row['total'];
        }

        return $count;
    }
}