<?php

namespace Model\User;

use Paginator\PaginatedInterface;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

class QuestionPaginatedModel implements PaginatedInterface
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
        $hasId = isset($filters['id']);

        return $hasId;
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
        $response = array();

        $params = array(
            'UserId' => (integer)$id,
            'offset' => (integer)$offset,
            'limit' => (integer)$limit
        );

        $query = "
            MATCH
            (u:User)
            WHERE u.qnoow_id = {UserId}
            MATCH
            (u)-[:ANSWERS]->(answer:Answer)-[:IS_ANSWER_OF]->(question:Question)
            OPTIONAL MATCH
            (possible_answers:Answer)-[:IS_ANSWER_OF]->(question)
            OPTIONAL MATCH
            (u)-[:ACCEPTS]-(accepted_answers:Answer)-[:IS_ANSWER_OF]->(question)
            OPTIONAL MATCH
            (u)-[rate:RATES]->(question)
            RETURN
            question,
            collect(distinct possible_answers) as possible_answers,
            answer.qnoow_id as answer,
            collect(distinct accepted_answers.qnoow_id) as accepted_answers,
            rate.rating AS rating
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
                $content['question']['id'] = $row['question']->getProperty('qnoow_id');
                $content['question']['text'] = $row['question']->getProperty('text');
                foreach ($row['possible_answers'] as $possibleAnswer) {
                    $answer = array();
                    $answer['id'] = $possibleAnswer->getProperty('qnoow_id');
                    $answer['text'] = $possibleAnswer->getProperty('text');
                    $content['possible_answers'][] = $answer;
                }
                $content['answer'] = $row['answer'];
                foreach ($row['accepted_answers'] as $acceptedAnswer) {
                    $content['accepted_answers'][] = $acceptedAnswer;
                }
                $content['rating'] = $row['rating'];

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
        $id = $filters['id'];
        $count = 0;

        $params = array(
            'UserId' => (integer)$id,
        );

        $query = "
            MATCH
            (u:User)
            WHERE u.qnoow_id = {UserId}
            MATCH
            (u)-[:ANSWERS]->(answer:Answer)-[:IS_ANSWER_OF]->(question:Question)
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