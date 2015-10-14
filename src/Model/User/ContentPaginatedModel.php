<?php

namespace Model\User;

use Everyman\Neo4j\Node;
use Paginator\PaginatedInterface;
use Model\Neo4j\GraphManager;

class ContentPaginatedModel implements PaginatedInterface
{
    /**
     * @var array
     */
    private static $validTypes = array('Audio', 'Video', 'Image');

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var TokensModel
     */
    protected $tokensModel;

    public function __construct(GraphManager $gm, TokensModel $tokensModel)
    {
        $this->gm = $gm;
        $this->tokensModel = $tokensModel;
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
        $hasId = isset($filters['id']);

        if (isset($filters['type'])) {
            $hasValidType = in_array($filters['type'], $this->getValidTypes());
        } else {
            $hasValidType = true;
        }

        $isValid = $hasId && $hasValidType;

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
        $qb = $this->gm->createQueryBuilder();
        $id = $filters['id'];

        $tokens = $this->tokensModel->getByUserOrResource($id);
        $socialNetworks = array();
        foreach ($tokens as $token) {
            $socialNetworks[] = $token['resourceOwner'];
        }

        $response = array();

        $linkType = 'Link';
        if (isset($filters['type'])) {
            $linkType = $filters['type'];
        }

        $qb->match("(u:User)")
            ->where("u.qnoow_id = { userId }")
            ->match("(u)-[r:LIKES]->(content:" . $linkType . ")");

        $whereSocialNetwork = array("(HAS (r.nekuno))");
        foreach ($socialNetworks as $socialNetwork) {
            $whereSocialNetwork [] = "(HAS (r.$socialNetwork))";
        }
        $qb->where(implode('OR', $whereSocialNetwork));

        if (isset($filters['tag'])) {
            $qb->match("(content)-[:TAGGED]->(filterTag:Tag)")
                ->where("filterTag.name = { tag }");
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

        foreach ($result as $row) {
            $content = array();

            $content['id'] = $row['id'];
            $content['type'] = $row['type'];
            $content['url'] = $row['content']->getProperty('url');
            $content['title'] = $row['content']->getProperty('title');
            $content['description'] = $row['content']->getProperty('description');
            $content['thumbnail'] = $row['content']->getProperty('thumbnail');
            $content['synonymous'] = array();

            if (isset($row['synonymous'])) {
                foreach ($row['synonymous'] as $synonymousLink) {
                    /* @var $synonymousLink Node */
                    $synonymous = array();
                    $synonymous['id'] = $synonymousLink->getId();
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

            $user = array();
            $user['user']['id'] = $id;
            $user['rate'] = $row['rate'];
            $content['user_rates'][] = $user;

            if ($row['content']->getProperty('embed_type')) {
                $content['embed']['type'] = $row['content']->getProperty('embed_type');
                $content['embed']['id'] = $row['content']->getProperty('embed_id');
            }

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
        $qb = $this->gm->createQueryBuilder();
        $count = 0;

        $tokens = $this->tokensModel->getByUserOrResource($id);
        $socialNetworks = array();
        foreach ($tokens as $token) {
            $socialNetworks[] = $token['resourceOwner'];
        }

        $linkType = 'Link';
        if (isset($filters['type'])) {
            $linkType = $filters['type'];
        }

        $qb->match("(u:User)")
            ->where("u.qnoow_id = { userId }")
            ->match("(u)-[r:LIKES]->(content:" . $linkType . ")");

        $whereSocialNetwork = array("(HAS (r.nekuno))");
        foreach ($socialNetworks as $socialNetwork) {
            $whereSocialNetwork [] = "(HAS (r.$socialNetwork))";
        }
        $qb->where(implode('OR', $whereSocialNetwork));

        if (isset($filters['tag'])) {
            $qb->match("(content)-[:TAGGED]->(filterTag:Tag)")
                ->where("filterTag.name = { tag }");
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
}