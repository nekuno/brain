<?php

namespace Model\User\Recommendation;

use Everyman\Neo4j\Query\ResultSet;

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
        return array('items' => $this->getContentsByPopularity($filters, $offset, $limit));
    }

    /**
     * Popularity = (likes / max_likes)^3 . We reverse that exponent for a sensible output to the user.
     * {@inheritDoc}
     */
    public function buildResponseFromResult(ResultSet $result, $id = null, $offset = null)
    {
        $response = parent::buildResponseFromResult($result, $id, $offset);

        /** @var ContentRecommendation $item */
        foreach ($response['items'] as $item) {
            $content = $item->getContent();
            $match = isset($content['popularity']) ? pow(floatval($content['popularity']), 1 / 3) : 0;
            $item->setMatch($match);
        }
        return $response;
    }
}