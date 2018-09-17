<?php

namespace Paginator;

use Model\Neo4j\Neo4jException;
use Symfony\Component\HttpFoundation\Request;

class ProposalRecommendationsPaginator extends Paginator
{
    public function paginate(array $filters, PaginatedInterface $paginated, Request $request)
    {
        $limit = $request->get('limit', $this->getDefaultLimit());
        $limit = min($limit, $this->getMaxLimit());

        $offset = 0;
        $locale = $request->get('locale', 'en');
        $filters['locale'] = $locale;

        $this->checkFilters($filters, $paginated);

        $slice = $paginated->slice($filters, $offset, $limit);
        try {
            $total = $paginated->countTotal($filters);
        } catch (Neo4jException $e) {
            var_dump($e->getQuery());
            throw $e;
        }

        $pagination = array();
        $pagination['total'] = $total;
        $pagination['offset'] = $offset;
        $pagination['limit'] = $limit;
        $pagination['prevLink'] = '';
        $pagination['nextLink'] = '';

        $result = array();
        $result['pagination'] = $pagination;
        $result['items'] = $slice;

        return $result;
    }

}