<?php

namespace Model\Question;

use Paginator\PaginatedInterface;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

class OldQuestionComparePaginatedManager implements PaginatedInterface
{
    /**
     * @var \Everyman\Neo4j\Client
     */
    protected $client;

    /**
     * @param \Everyman\Neo4j\Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
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
        $response = array();

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
            (u2)-[:ANSWERS]-(answer2:Answer)-[:IS_ANSWER_OF]-(question)
        ";
        if ($showOnlyCommon) {
            $commonQuery = "
                MATCH
                (u2)-[:ANSWERS]-(answer2:Answer)-[:IS_ANSWER_OF]-(question)
            ";
        }

        $query = "
            MATCH
            (u:User), (u2:User)
            WHERE u.qnoow_id = {UserId} AND u2.qnoow_id = {UserId2}
            MATCH
            (u)-[:ANSWERS]-(answer:Answer)-[:IS_ANSWER_OF]-(question:Question)
            WHERE EXISTS(answer.text_$locale)
        ";
        $query .= $commonQuery;
        $query .= "
            OPTIONAL MATCH
            (possible_answers:Answer)-[:IS_ANSWER_OF]-(question)
            OPTIONAL MATCH
            (u)-[:ACCEPTS]-(accepted_answers:Answer)-[:IS_ANSWER_OF]-(question)
            OPTIONAL MATCH
            (u)-[rate:RATES]-(question)

            OPTIONAL MATCH
            (u2)-[:ACCEPTS]-(accepted_answers2:Answer)-[:IS_ANSWER_OF]-(question)
            OPTIONAL MATCH
            (u2)-[rate2:RATES]-(question)

            RETURN
            question,
            collect(distinct possible_answers) as possible_answers,

            id(answer) as answer,
            collect(distinct id(accepted_answers)) as accepted_answers,
            rate.rating AS rating,

            id(answer2) as answer2,
            collect(distinct id(accepted_answers2)) as accepted_answers2,
            rate2.rating AS rating2

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

        //Execute query
        try {
            $result = $contentQuery->getResultSet();

            foreach ($result as $row) {
                $content = array();

                $question = array();
                $question['id'] = $row['question']->getId();
                $question['text'] = $row['question']->getProperty('text_' . $locale);
                foreach ($row['possible_answers'] as $possibleAnswer) {
                    $answer = array();
                    $answer['id'] = $possibleAnswer->getId();
                    $answer['text'] = $possibleAnswer->getProperty('text_' . $locale);
                    $question['answers'][] = $answer;
                }
                $content['question'] = $question;

                $user1 = array();
                $user1['user']['id'] = $id;
                $user1['answer'] = $row['answer'];
                foreach ($row['accepted_answers'] as $acceptedAnswer) {
                    $user1['accepted_answers'][] = $acceptedAnswer;
                }
                $user1['rating'] = $row['rating'];
                $content['user_answers'][] = $user1;

                if (null != $row['answer2']) {
                    $user2 = array();
                    $user2['user']['id'] = $id2;
                    $user2['answer'] = $row['answer2'];
                    foreach ($row['accepted_answers2'] as $acceptedAnswer) {
                        $user2['accepted_answers'][] = $acceptedAnswer;
                    }
                    $user2['rating'] = $row['rating2'];
                    $content['user_answers'][] = $user2;
                }

                $response[] = $content;
            }

        } catch (\Exception $e) {
            throw $e;
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