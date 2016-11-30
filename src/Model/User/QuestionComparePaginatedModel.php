<?php

namespace Model\User;

use Paginator\PaginatedInterface;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use Everyman\Neo4j\Query\Row;

class QuestionComparePaginatedModel implements PaginatedInterface
{
    /**
     * @var \Everyman\Neo4j\Client
     */
    protected $client;

    /**
     * @var AnswerModel
     */
    protected $am;

    /**
     * @param \Everyman\Neo4j\Client $client
     * @param AnswerModel $am
     */
    public function __construct(Client $client, AnswerModel $am)
    {
        $this->client = $client;
        $this->am = $am;
    }

    /**
     * Hook point for validating the query.
     * @param array $filters
     * @return boolean
     */
    public function validateFilters(array $filters)
    {
        $hasIds = isset($filters['id']) && isset($filters['id2']);
        $hasLocale = isset($filters['locale']);

        return $hasIds && $hasLocale;
    }

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

        $id = $filters['id'];
        $id2 = $filters['id2'];
        $locale = $filters['locale'];

        $showOnlyCommon = false;
        if (isset($filters['showOnlyCommon'])) {
            $showOnlyCommon = $filters['showOnlyCommon'];
        }

        $params = array(
            'UserId' => (integer)$id,
            'UserId2' => (integer)$id2,
            'offset' => (integer)$offset,
            'limit' => (integer)$limit
        );

        $commonQuery = "
            OPTIONAL MATCH
            (u2)-[ua2:ANSWERS]-(answer2:Answer)-[:IS_ANSWER_OF]-(question)
        ";
        if ($showOnlyCommon) {
            $commonQuery = "
                MATCH
                (u2)-[ua2:ANSWERS]-(answer2:Answer)-[:IS_ANSWER_OF]-(question)
            ";
        }

        $query = "
            MATCH
            (u:User), (u2:User)
            WHERE u.qnoow_id = {UserId} AND u2.qnoow_id = {UserId2}
            MATCH
            (u)-[ua:ANSWERS]-(answer:Answer)-[:IS_ANSWER_OF]-(question:Question)
            WHERE EXISTS(answer.text_$locale)
            WITH question, answer, ua, u, u2

        ";
        $query .= $commonQuery;
        $query .= "
            OPTIONAL MATCH
            (u)-[:ACCEPTS]-(acceptedAnswers:Answer)-[:IS_ANSWER_OF]-(question)
            OPTIONAL MATCH
            (u)-[rate:RATES]-(question)
            OPTIONAL MATCH
            (u2)-[:ACCEPTS]-(acceptedAnswers2:Answer)-[:IS_ANSWER_OF]-(question)
            OPTIONAL MATCH
            (u2)-[rate2:RATES]-(question)

            OPTIONAL MATCH
            (possible_answers:Answer)-[:IS_ANSWER_OF]-(question)
            RETURN
            question,
            {
                question: question,
                answer: answer,
                userAnswer: ua,
                rates: rate,
                answers: collect(distinct possible_answers),
                acceptedAnswers: collect(distinct acceptedAnswers)
            } as other_questions,
            {
                question: question,
                answer: answer2,
                userAnswer: ua2,
                rates: rate2,
                answers: collect(distinct possible_answers),
                acceptedAnswers: collect(distinct acceptedAnswers2)
            } as own_questions

            ORDER BY id(question)
            SKIP {offset}
            LIMIT {limit}
            ;
         ";

        //Create the Neo4j query object
        $contentQuery = new Query(
            $this->client,
            $query,
            $params
        );

        $result = $contentQuery->getResultSet();

        $own_questions_results = array();
        $other_questions_results = array();
        /* @var $row Row */
        foreach ($result as $row) {
            if ($row->offsetGet('own_questions')->offsetExists('userAnswer')) {
                $own_question = $row->offsetGet('own_questions');
                $questionId = $own_question->offsetGet('question')->getId();
                $own_questions_results['questions'][$questionId] = $this->am->build($own_question, $locale);
            }
            if ($row->offsetGet('other_questions')->offsetExists('userAnswer')) {
                $other_question = $row->offsetGet('other_questions');
                $questionId = $other_question->offsetGet('question')->getId();
                $other_questions_results['questions'][$questionId] = $this->am->build($other_question, $locale);
            }
        }
        $own_questions_results['userId'] = $id2;
        $other_questions_results['userId'] = $id;

        $resultArray = array($other_questions_results, $own_questions_results);

        return $resultArray;
    }

    /**
     * Counts the total results from queryset.
     * @param array $filters
     * @throws \Exception
     * @return int
     */
    public function countTotal(array $filters)
    {
        $count = 0;

        $id = $filters['id'];

        $params = array(
            'UserId' => (integer)$id,
        );

        $commonQuery = "";
        if (isset($filters['showOnlyCommon'])) {
            $id2 = $filters['id2'];
            if ($filters['showOnlyCommon']) {
                $commonQuery = "
                    MATCH
                    (u2)-[:ANSWERS]-(answer2:Answer)-[:IS_ANSWER_OF]-(question)
                    WHERE u2.qnoow_id = {UserId2}
                ";
                $params['UserId2'] = (integer)$id2;
            }
        }

        $query = "
            MATCH
            (u:User)
            WHERE u.qnoow_id = {UserId}
            MATCH
            (u)-[:ANSWERS]-(answer:Answer)-[:IS_ANSWER_OF]-(question:Question)
        ";
        $query .= $commonQuery;
        $query .= "
            RETURN
            count(distinct question) as total
            ;
         ";

        //Create the Neo4j query object
        $contentQuery = new Query(
            $this->client,
            $query,
            $params
        );

        //Execute query
        try {
            $result = $contentQuery->getResultSet();

            foreach ($result as $row) {
                $count = $row['total'];
            }

        } catch (\Exception $e) {
            throw $e;
        }

        return $count;
    }
}