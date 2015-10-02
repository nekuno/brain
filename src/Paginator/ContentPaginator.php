<?php
/**
 * Created by PhpStorm.
 * User: yawmoght
 */

namespace Paginator;


use Model\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request;

class ContentPaginator extends Paginator
{


    /**
     * @param array $filters
     * @param PaginatedInterface $paginated
     * @param Request $request
     * @return array
     */
    public function paginate(array $filters, PaginatedInterface $paginated, Request $request)
    {
        $limit = $request->get('limit', $this->getDefaultLimit());
        $limit = min($limit, $this->getMaxLimit());

        $offset = $request->get('offset', 0);

        if (!$paginated->validateFilters($filters)) {
            $e = new ValidationException(sprintf('Invalid filters in "%s"', get_class($paginated)));
            throw $e;
        }

        $slice = $paginated->slice($filters, $offset, $limit);
        $total = $paginated->countTotal($filters);

        $prevLink = $this->createPrevLink($request, $offset, $limit);
        $nextLink = $this->createNextLink($request, $offset, $limit, $total);

        $pagination = array();
        $pagination['total'] = $total;
        $pagination['offset'] = $offset;
        $pagination['limit'] = $limit;
        $pagination['prevLink'] = $prevLink;
        $pagination['nextLink'] = $nextLink;

        $result = array();
        $result['pagination'] = $pagination;
        $result['items'] = $slice['items'];

        $foreign = 0;
        if (isset($filters['foreign'])) {
            $foreign = $filters['foreign'];
        }

        $newForeign = isset($slice['newForeign']) ? $slice['newForeign']: 0;

        if ($result['pagination']['nextLink'] != null) {
            if ($foreign != null) {
                $result['pagination']['nextLink'] = str_replace(
                    'foreign=' . $foreign,
                    'foreign=' . $newForeign,
                    $result['pagination']['nextLink']
                );
            } else {
                $result['pagination']['nextLink'] .= ('&foreign=' . $newForeign);
            }
        }

        return $result;
    }


}