<?php

namespace Paginator;

use Model\Neo4j\Neo4jException;
use Model\Recommendation\AbstractUserRecommendator;
use Symfony\Component\HttpFoundation\Request;

class ProposalRecommendationsPaginator extends Paginator
{
    public function paginate(array $filters, PaginatedInterface $paginated, Request $request)
    {
        $limit = $request->get('limit', $this->getDefaultLimit());
        $limit = min($limit, $this->getMaxLimit());

        $offset = $request->get('offset', array('candidates' => 0, 'proposals' => 0));
        $locale = $request->get('locale', 'en');
        $filters['locale'] = $locale;

        $this->checkFilters($filters, $paginated);

        $offset = ($paginated instanceof AbstractUserRecommendator) ? $offset['candidates'] : $offset['proposals'] ;

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
        $result['items'] = $slice;

        return $result;
    }

}