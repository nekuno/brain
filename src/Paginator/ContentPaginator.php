<?php

namespace Paginator;

use Model\Exception\ValidationException;
use Model\User\Recommendation\ContentRecommendation;
use Model\User\Recommendation\UserRecommendation;
use Symfony\Component\HttpFoundation\Request;

class ContentPaginator extends Paginator
{
    /**
     * @param array $filters
     * @param PaginatedInterface $paginated Slice returns array (items, newForeign)
     * @param Request $request
     * @return array
     */
    public function paginate(array $filters, PaginatedInterface $paginated, Request $request)
    {
        $limit = $request->get('limit', $this->getDefaultLimit());
        $limit = min($limit, $this->getMaxLimit());

        $offset = $request->get('offset', 0);
        $locale = $request->get('locale', null);
        $filters['locale'] = $locale;

        if (!$paginated->validateFilters($filters)) {
            throw new ValidationException(array(), sprintf('Invalid filters in "%s"', get_class($paginated)));
        }

        $slice = $paginated->slice($filters, $offset, $limit);
        $total = $paginated->countTotal($filters);

        $foreign = 0;
        if (isset($filters['foreign'])) {
            $foreign = $filters['foreign'];
        }

        $newForeign = isset($slice['newForeign']) ? $slice['newForeign'] : $foreign;

        $ignored = 0;
        if (isset($filters['ignored'])) {
            $ignored = $filters['ignored'];
        }

        $newIgnored = isset($slice['newIgnored']) ? $slice['newIgnored'] : $ignored;

        $prevLink = $this->createContentPrevLink($request, $offset, $limit, $foreign, $newForeign, $newIgnored);
        if (count($slice['items']) < $limit) {
            $nextLink = null;
        } else {
            $nextLink = $this->createContentNextLink($request, $offset, $limit, $total, $foreign, $newForeign, $ignored, $newIgnored);
        }
        $pagination = array();
        $pagination['total'] = $total;
        $pagination['offset'] = $offset;
        $pagination['limit'] = $limit;
        $pagination['prevLink'] = $prevLink;
        $pagination['nextLink'] = $nextLink;

        $result = array();
        $result['pagination'] = $pagination;
        $result['items'] = $slice['items'];

        return $result;
    }

    /**
     * @param Request $request
     * @param $offset
     * @param $limit
     * @param $foreign
     * @param $foreignContent
     * @param $ignored
     * @return string
     */
    protected function createContentPrevLink(Request $request, $offset, $limit, $foreign, $foreignContent, $ignored)
    {
        $parentPrev = parent::createPrevLink($request, $offset, $limit);
        $prevLink = $this->addForeign($parentPrev, $foreign, false, $foreignContent);

        return $this->addIgnored($prevLink, $ignored, false);
    }

    /**
     * @param Request $request
     * @param $offset
     * @param $limit
     * @param $total
     * @param $foreign
     * @param $foreignContent
     * @param $ignored
     * @param $newIgnored
     * @return string
     */
    protected function createContentNextLink(Request $request, $offset, $limit, $total, $foreign, $foreignContent, $ignored, $newIgnored)
    {
        $parentNext = parent::createNextLink($request, $offset, $limit, $total);
        $nextLink = $this->addForeign($parentNext, $foreign, true, $foreignContent - $foreign);

        return $this->addIgnored($nextLink, $ignored, true, $newIgnored - $ignored);
    }

    /**
     * @param $url
     * @param $foreign
     * @param bool $next
     * @param int $newForeign
     * @return string
     */
    protected function addForeign($url, $foreign, $next = false, $newForeign = 0)
    {
        if (!$url || $newForeign === 0) {
            return $url;
        }

        if ($next && $foreign < 0) {
            return null; //database completely searched
        }

        $url_parts = parse_url($url);
        parse_str($url_parts['query'], $params);

        $params['offset'] = isset($params['offset']) ? $params['offset'] : 0;
        if ($next) {
            $params['offset'] -= $newForeign;
            $params['foreign'] = $foreign + $newForeign;
        } else {
            if (isset($params['foreign']) && $params['foreign']) {
                $params['foreign'] = $foreign;
                $params['offset'] += $params['limit'];
            }
        }

        $url_parts['query'] = http_build_query($params);

        return http_build_url($url_parts);
    }

    /**
     * @param $url
     * @param $ignored
     * @param bool $next
     * @param $newIgnored
     * @return string
     */
    protected function addIgnored($url, $ignored, $next = false, $newIgnored = 0)
    {
        if (!$url || $newIgnored === 0) {
            return $url;
        }

        $url_parts = parse_url($url);
        parse_str($url_parts['query'], $params);

        $params['offset'] = isset($params['offset']) ? $params['offset'] : 0;
        if ($next) {
            $params['offset'] = ($params['offset'] - $newIgnored) >= 0 ? $params['offset'] - $newIgnored : $params['offset'];
            $params['ignored'] = $ignored + $newIgnored;

        } else {
            if (isset($params['ignored']) && $params['ignored']) {
                $params['ignored'] = $ignored;
                $params['offset'] += $params['limit'];
            }
        }

        $url_parts['query'] = http_build_query($params);

        return http_build_url($url_parts);
    }

}