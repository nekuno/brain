<?php

namespace Model\User;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;

class AnswerModel
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
     * Counts the total results from queryset.
     * @param array $filters
     * @throws \Exception
     * @return int
     */
    public function countTotal(array $filters)
    {
        $count = 0;

        $query = "
            MATCH
            (u:User)
            WHERE u.qnoow_id = {UserId}
            MATCH
            (u)-[r:RATES]->(q:Question)
            RETURN
            count(distinct r) as total
            ;
         ";

        //Create the Neo4j query object
        $contentQuery = new Query(
            $this->client,
            $query,
            array(
                'UserId' => (integer)$filters['id'],
            )
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

    /**
     * @param array $data
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function create(array $data)
    {

        $template = "MATCH (user:User), (question:Question), (answer:Answer)"
            . " WHERE user.qnoow_id = {userId} AND id(question) = {questionId} AND id(answer) = {answerId}"
            . " CREATE UNIQUE (user)-[:ANSWERS]->(answer)"
            . ", (user)-[r:RATES]->(question)"
            . " SET r.rating = {rating}"
            . " WITH user, question, answer"
            . " OPTIONAL MATCH (pa:Answer)-[:IS_ANSWER_OF]->(question)"
            . " WHERE id(pa) IN {acceptedAnswers}"
            . " CREATE UNIQUE (user)-[:ACCEPTS]->(pa)"
            . " RETURN answer"
        ;

        $template .= ";";

        //Create the Neo4j query object
        $query = new Query(
            $this->client,
            $template,
            $data
        );

        return $query->getResultSet();

    }

    /**
     * @param array $data
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function update(array $data)
    {

        $template = "MATCH (user:User), (question:Question), (oldAnswer:Answer)"
            . " WHERE user.qnoow_id = {userId} AND id(question) = {questionId} AND id(oldAnswer) = {currentId}"
            . " WITH user, question, oldAnswer"
            . " OPTIONAL MATCH (user)-[r:RATES]->(question)"
            . ", (user)-[r1:ANSWERS]->(oldAnswer)"
            . ", (user)-[r2:ACCEPTS]->(oldAcceptedAnswer:Answer)"
            . " SET r.rating = {rating}"
            . " DELETE r1, r2"
            . " WITH user, question"
            . " OPTIONAL MATCH (answer:Answer)"
            . " WHERE id(answer) = {answerId}"
            . " CREATE UNIQUE (user)-[:ANSWERS]->(answer)"
            . " WITH user, question, answer"
            . " OPTIONAL MATCH (possibleAnswers:Answer)-[:IS_ANSWER_OF]->(question)"
            . " WHERE id(possibleAnswers) IN {acceptedAnswers}"
            . " CREATE UNIQUE (user)-[:ACCEPTS]->(possibleAnswers)"
            . " RETURN answer"
        ;

        $template .= ";";

        //Create the Neo4j query object
        $query = new Query(
            $this->client,
            $template,
            $data
        );

        return $query->getResultSet();
    }

    /**
     * @param array $data
     * @return \Everyman\Neo4j\Query\ResultSet
     */
    public function explain(array $data)
    {

        $template = "MATCH"
            . " (user:User)-[r:ANSWERS]->(answer:Answer)"
            . " WHERE user.qnoow_id = {userId} AND id(answer) = {answerId}"
            . " SET r.explanation = {explanation}"
            . " RETURN answer";

        $query = new Query($this->client, $template, $data);

        return $query->getResultSet();

    }
} 