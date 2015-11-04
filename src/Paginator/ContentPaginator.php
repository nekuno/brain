<?php
/**
 * Created by PhpStorm.
 * User: yawmoght
 */

namespace Paginator;

use Model\Exception\ValidationException;
use Model\LinkModel;
use Symfony\Component\HttpFoundation\Request;

class ContentPaginator extends Paginator
{
    protected $linkModel;

    function __construct(LinkModel $linkModel, $maxLimit = 50, $defaultLimit = 20)
    {
        parent::__construct($maxLimit, $defaultLimit);
        $this->linkModel = $linkModel;
    }

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
        $foreignContent = 0;
        foreach ($slice['items'] as $item) {
            if ($item['match'] == 0) {
                $foreignContent++;
            }
        }

        $prevLink = $this->createContentPrevLink($request, $offset, $limit, $newForeign, $foreignContent);
        $nextLink = $this->createContentNextLink($request, $offset, $limit, $total, $newForeign, $foreignContent);

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
     * @return string
     */
    protected function createContentPrevLink(Request $request, $offset, $limit, $foreign, $foreignContent)
    {
        $parentPrev = parent::createPrevLink($request, $offset, $limit);

        return $this->addForeign($parentPrev, $foreign, false, $foreignContent);
    }

    /**
     * @param Request $request
     * @param $offset
     * @param $limit
     * @param $total
     * @param $foreign
     * @param $foreignContent
     * @return string
     */
    protected function createContentNextLink(Request $request, $offset, $limit, $total, $foreign, $foreignContent)
    {
        $parentNext = parent::createNextLink($request, $offset, $limit, $total);

        return $this->addForeign($parentNext, $foreign, true, $foreignContent);

    }

    /**
     * @param $url
     * @param $foreign
     * @param bool $next
     * @param int $foreignContent
     * @return string
     */
    protected function addForeign($url, $foreign, $next = false, $foreignContent = 0)
    {
        if (!$url || $foreignContent === 0) {
            return $url;
        }

        if ($next && $foreign < 0) {
            return null; //database completely searched
        }

        $url_parts = parse_url($url);
        parse_str($url_parts['query'], $params);

        if ($next) {

            $params['offset'] -= $foreignContent;
            $params['foreign'] = $foreign;

        } else {

            if (isset($params['foreign']) && $params['foreign']) {
                $params['foreign'] = max($foreign - 2 * $params['limit'], 0); //best approximation
                $params['offset'] += $params['limit'];
            }

        }

        $url_parts['query'] = http_build_query($params);

        return http_build_url($url_parts);
    }

}