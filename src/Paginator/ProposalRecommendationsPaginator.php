<?php

namespace Paginator;

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
        $total = $paginated->countTotal($filters);

        $pagination = array();
        $pagination['total'] = $total;
        $pagination['offset'] = $offset;
        $pagination['limit'] = $limit;
        $pagination['prevLink'] = '';
        $pagination['nextLink'] = '';

        $result = array();
        $result['pagination'] = $pagination;
        $result['items'] = $slice['items'];

        return $result;
    }

}