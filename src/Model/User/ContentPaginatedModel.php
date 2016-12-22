<?php

namespace Model\User;

use Everyman\Neo4j\Node;
use Model\LinkModel;
use Paginator\PaginatedInterface;
use Model\Neo4j\GraphManager;
use Service\Validator;

class ContentPaginatedModel implements PaginatedInterface
{
    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var TokensModel
     */
    protected $tokensModel;

    /**
     * @var LinkModel
     */
    protected $linkModel;

    /**
     * @var Validator
     */
    protected $validator;

    public function __construct(GraphManager $gm, TokensModel $tokensModel, LinkModel $linkModel, Validator $validator)
    {
        $this->gm = $gm;
        $this->tokensModel = $tokensModel;
        $this->linkModel = $linkModel;
        $this->validator = $validator;
    }

    /**
     * Hook point for validating the query.
     * @param array $filters
     * @return boolean
     */
    public function validateFilters(array $filters)
    {
        $userId = isset($filters['id'])? $filters['id'] : null;
        $this->validator->validateUserId($userId);

        return $this->validator->validateRecommendateContent($filters, $this->getChoices());
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
        $types = isset($filters['type']) ? $filters['type'] : array();

        $response = array();

        $qb->match("(u:User)")
            ->where("u.qnoow_id = { userId }")
            ->match("(u)-[r:LIKES]->(content:Link {processed: 1})");
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

        //TODO: Build the result (ContentRecommendationPaginatedModel as example) using LinkModel.

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
        $types = isset($filters['type']) ? $filters['type'] : array();

        $qb = $this->gm->createQueryBuilder();
        $count = 0;

        $qb->match("(u:User)")
            ->where("u.qnoow_id = { userId }")
            ->match("(u)-[r:LIKES]->(content:Link {processed: 1})");

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
        $types = $this->linkModel->getValidTypes();
        $qb = $this->gm->createQueryBuilder();
        $qb->match("(u:User {qnoow_id: { userId }})")
            ->setParameter('userId', $userId);
        $with = 'u,';
        foreach ($types as $type) {
            $qb->optionalMatch("(u)-[:LIKES]->(content$type:$type)")
                ->where('content' . $type . '.processed = 1');
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

    // TODO: Useful for filtering by social networks
    private function buildSocialNetworkCondition($userId, $relationship)
    {
        $tokens = $this->tokensModel->getByUserOrResource($userId);
        $socialNetworks = array();
        foreach ($tokens as $token) {
            $socialNetworks[] = $token['resourceOwner'];
        }
        $whereSocialNetwork = array();
        foreach ($socialNetworks as $socialNetwork) {
            $whereSocialNetwork[] = "EXISTS ($relationship.$socialNetwork)";
        }

        return implode(' OR ', $whereSocialNetwork);
    }

    protected function getChoices()
    {
        return array('type' => $this->linkModel->getValidTypes());
    }
}