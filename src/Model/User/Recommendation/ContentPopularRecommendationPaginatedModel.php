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
        return array('items' => $this->getContentsByPopularity($filters, $offset, $limit));
    }

    /**
     * Popularity = (likes / max_likes)^3 . We reverse that exponent for a sensible output to the user.
     * {@inheritDoc}
     */
    public function buildResponseFromResult($result, $id = null, $offset = null)
    {
        $response = parent::buildResponseFromResult($result, $id, $offset);

        foreach ($response['items'] as &$item) {
            $item['match'] = isset($item['content']) && isset($item['content']['popularity']) ? pow(floatval($item['content']['popularity']), 1 / 3) : 0;
        }
        return $response;
    }
}