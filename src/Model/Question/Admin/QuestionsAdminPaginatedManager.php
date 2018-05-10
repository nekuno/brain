<?php

namespace Model\Question\Admin;

use Everyman\Neo4j\Query\Row;
use Paginator\PaginatedInterface;

class QuestionsAdminPaginatedManager extends QuestionAdminManager implements PaginatedInterface
{
    public function validateFilters(array $filters)
    {
        return isset($filters['locale']);
    }

    public function slice(array $filters, $offset, $limit)
    {
        $order = $this->getOrder($filters);
        $textAttribute = $this->questionAdminBuilder->buildTextAttribute($filters['locale']);

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(q:Question)')
            ->optionalMatch('(q)<-[s:SKIPS]-(:User)')
            ->with('q', 'count(s) AS skipped')
            ->optionalMatch('(q)<-[r:RATES]-(:User)')
            ->with('q', 'skipped', 'count(r) AS answered')
            ->orderBy($order);

        if (!is_null($offset)) {
            $qb->skip($offset);
        }
        if (!is_null($limit)) {
            $qb->limit($limit);
        }

        $qb->optionalMatch('(q)<-[:IS_ANSWER_OF]-(a:Answer)')
            ->with('q', 'skipped', 'answered', 'a', "CASE WHEN EXISTS(a.$textAttribute) THEN 0 ELSE 1 END AS lacksLocale")
            ->with('q', 'skipped', 'answered', 'collect(a) AS answers', 'sum(lacksLocale) AS localeMissing')
            ->with('q', 'skipped', 'answered', 'answers', "localeMissing + CASE WHEN EXISTS(q.$textAttribute) THEN 0 ELSE 1 END AS localeMissing");

        $qb->optionalMatch('(q)-[:CATEGORY_OF]-(qc:QuestionCategory)-[:INCLUDED_IN]-(m:Mode)')
            ->returns('q', 'skipped', 'answered', 'answers', 'localeMissing', 'collect(m.id) AS categories')
            ->orderBy($order);

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        $return = array();

        /** @var Row $row */
        foreach ($result as $row) {
            $return[] = $this->questionAdminBuilder->build($row);
        }

        return $return;
    }

    public function countTotal(array $filters)
    {
        $locale = $filters['locale'];

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(q:Question)')
            ->where("EXISTS(q.text_$locale)");

        $qb->returns('count(q) AS amount');

        $result = $qb->getQuery()->getResultSet();

        return $result->current()->offsetGet('amount');
    }

    protected function getOrder(array $filters)
    {
        switch($filters['order']) {
            case 'id':
                $order = 'id(q)';
                break;
            default:
                $order = $filters['order'] ? $filters['order'] : 'answered';
                break;
        }
        $orderDir = $filters['orderDir'] ? $filters['orderDir'] : 'desc';

        return $order . ' ' . $orderDir;
    }
}