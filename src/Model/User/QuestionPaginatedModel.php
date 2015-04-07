<?php

namespace Model\User;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Everyman\Neo4j\Relationship;
use Model\Neo4j\GraphManager;
use Model\Questionnaire\QuestionModel;
use Paginator\PaginatedInterface;

class QuestionPaginatedModel implements PaginatedInterface
{

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var AnswerModel
     */
    protected $am;

    /**
     * @param GraphManager $gm
     */
    public function __construct(GraphManager $gm, AnswerModel $am)
    {
        $this->gm = $gm;
        $this->am = $am;
    }

    /**
     * Hook point for validating the query.
     * @param array $filters
     * @return boolean
     */
    public function validateFilters(array $filters)
    {
        $hasId = isset($filters['id']);
        $hasLocale = isset($filters['locale']);

        return $hasId && $hasLocale;
    }

    /**
     * Slices the query according to $offset, and $limit
     * @param array $filters
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function slice(array $filters, $offset, $limit)
    {
        $userId = (integer)$filters['id'];
        $locale = $filters['locale'];
        $response = array();

        $qb = $this->gm->createQueryBuilder();
        $qb
            ->match('(u:User {qnoow_id: { userId }})')
            ->setParameter('userId', $userId)
            ->match('(u)-[ua:ANSWERS]-(a:Answer)-[:IS_ANSWER_OF]-(q:Question)')
            ->where("HAS(q.text_$locale)")
            ->optionalMatch('(answers:Answer)-[:IS_ANSWER_OF]-(q)')
            ->optionalMatch('(u)-[:ACCEPTS]-(acceptedAnswers:Answer)-[:IS_ANSWER_OF]-(q)')
            ->optionalMatch('(u)-[r:RATES]-(q)')
            ->with('a', 'ua', 'q', 'COLLECT(DISTINCT acceptedAnswers) AS acceptedAnswers', 'r', 'answers')
            ->returns('a AS answer', 'ua AS userAnswer', 'acceptedAnswers', 'q AS question', 'r AS rates', 'COLLECT(DISTINCT answers) AS answers')
            ->orderBy('ua.answeredAt DESC')
            ->skip('{ offset }')
            ->setParameter('offset', (integer)$offset)
            ->limit('{ limit }')
            ->setParameter('limit', (integer)$limit);

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        /* @var $row Row */
        foreach ($result as $row) {

            $response[] = $this->am->build($row, $locale);
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
        $id = (integer)$filters['id'];
        $locale = $filters['locale'];

        $qb = $this->gm->createQueryBuilder();
        $qb
            ->match('(u:User {qnoow_id: { id }})')
            ->setParameter('id', $id)
            ->match('(u)-[:ANSWERS]-(answer:Answer)-[:IS_ANSWER_OF]-(question:Question)')
            ->where("HAS(answer.text_$locale)")
            ->returns('COUNT(DISTINCT question) AS total');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        return $row->offsetGet('total');
    }
}