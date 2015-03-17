<?php

namespace Model\User;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
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
     * @var QuestionModel
     */
    protected $qm;

    /**
     * @param GraphManager $gm
     */
    public function __construct(GraphManager $gm, QuestionModel $qm)
    {
        $this->gm = $gm;
        $this->qm = $qm;
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
        $id = (integer)$filters['id'];
        $locale = $filters['locale'];
        $response = array();

        $qb = $this->gm->createQueryBuilder();
        $qb
            ->match('(u:User {qnoow_id: { id }})')
            ->setParameter('id', $id)
            ->match('(u)-[r1:ANSWERS]-(answer:Answer)-[:IS_ANSWER_OF]-(question:Question)')
            ->where("HAS(answer.text_$locale)")
            ->optionalMatch('(possible_answers:Answer)-[:IS_ANSWER_OF]-(question)')
            ->optionalMatch('(u)-[:ACCEPTS]-(accepted_answers:Answer)-[:IS_ANSWER_OF]-(question)')
            ->optionalMatch('(u)-[rate:RATES]-(question)')
            ->with(
                'question, possible_answers',
                'ID(answer) AS answer',
                'r1.explanation AS explanation',
                'r1.answeredAt AS answeredAt',
                'COLLECT(DISTINCT id(accepted_answers)) AS accepted_answers',
                'rate.rating AS rating'
            )
            ->orderBy('id(possible_answers)')
            ->returns(
                'question',
                'COLLECT(DISTINCT possible_answers) AS possible_answers',
                'answer',
                'explanation',
                'answeredAt',
                'accepted_answers',
                'rating'
            )
            ->orderBy('answeredAt DESC')
            ->skip('{ offset }')
            ->setParameter('offset', (integer)$offset)
            ->limit('{ limit }')
            ->setParameter('limit', (integer)$limit);

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        foreach ($result as $row) {

            /* @var $question Node */
            $question = $row['question'];

            $stats = $this->qm->getQuestionStats($question->getId());

            $responseQuestion = array(
                'id' => $question->getId(),
                'text' => $question->getProperty('text_' . $locale),
                'totalAnswers' => $stats[$question->getId()]['totalAnswers'],
            );

            foreach ($row['possible_answers'] as $possibleAnswer) {

                /* @var $possibleAnswer Node */
                $responseQuestion['answers'][] = array(
                    'id' => $possibleAnswer->getId(),
                    'text' => $possibleAnswer->getProperty('text_' . $locale),
                    'nAnswers' => $stats[$question->getId()]['answers'][$possibleAnswer->getId()]['nAnswers'],
                );

            }

            $user = array(
                'id' => $id,
                'answer' => $row['answer'],
                'answeredAt' => floor($row['answeredAt'] / 1000),
                'explanation' => $row['explanation'],
                'accepted_answers' => array(),
                'rating' => $row['rating'],
            );

            foreach ($row['accepted_answers'] as $acceptedAnswer) {
                $user['accepted_answers'][] = $acceptedAnswer;
            }

            $response[] = array(
                'question' => $responseQuestion,
                'user_answers' => $user,
            );
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