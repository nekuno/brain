<?php

namespace Model\User\Similarity;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class SimilarityModel
{

    /**
     * @var Client
     */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getSimilarity($id1, $id2)
    {

    }

    public function getSimilarityByQuestions($idA, $idB)
    {
        $parameters = array(
            'idA' => (integer)$idA,
            'idB' => (integer)$idB,
        );

        $template = "
            MATCH (userA:User {qnoow_id: {idA}}), (userB:User {qnoow_id: {idB}})
            MATCH (userA)-[:ANSWERS]-(answerA:Answer)-[:IS_ANSWER_OF]-(q:Question)
            MATCH (userB)-[:ANSWERS]-(answerB:Answer)-[:IS_ANSWER_OF]-(q)
            WITH userA, userB, q, CASE WHEN answerA = answerB THEN 1 ELSE 0 END AS equal
            WITH userA, userB, toFloat(COUNT(q)) AS PC, toFloat(SUM(equal)) AS RI
            WITH userA, userB, CASE WHEN PC <= 0 THEN toFloat(0) ELSE RI/PC - 1/PC END AS similarity
            WITH userA, userB, CASE WHEN similarity < 0 THEN toFloat(0) ELSE similarity END AS similarity
            MERGE (userA)-[s:SIMILARITY]-(userB)
            SET s.questions = similarity
            RETURN similarity
        ";

        $query = new Query($this->client, $template, $parameters);

        $result = $query->getResultSet();

        $similarity = 0;
        if ($result->count() > 0) {
            /* @var $row Row */
            $row = $result->current();
            /* @var $node Node */
            $similarity = $row->offsetGet('similarity');
        }

        return $similarity;
    }

    public function getSimilarityByInterests($idA, $idB)
    {
        $parameters = array(
          'idA' => (integer)$idA,
          'idB' => (integer)$idB,
        );

        $template = "
            MATCH (userA:User {qnoow_id: {idA}}), (userB:User {qnoow_id: {idB}})
            MATCH (userA)-[:LIKES]-(l:Link)-[:LIKES]-(userB)
            WHERE userA <> userB AND HAS(l.unpopularity)
	        WITH userA, userB, COUNT(DISTINCT l) AS numberCommonContent, SUM(l.unpopularity) AS common
	        WHERE numberCommonContent > 4
	        WITH userA, userB, common

            OPTIONAL MATCH (userA)-[:LIKES]-(l1:Link)
	        WHERE NOT (userB)-[:LIKES]->(l1) AND HAS(l1.popularity)
	        WITH userA, userB, common, SUM(l1.popularity) AS onlyUserA

            OPTIONAL MATCH (userB)-[:LIKES]-(l2:Link)
            WHERE NOT (userA)-[:LIKES]->(l2) AND HAS(l2.popularity)
	        WITH userA, userB, common, onlyUserA, SUM(l2.popularity) AS onlyUserB

            WITH userA, userB, sqrt( common / (onlyUserA + common)) * sqrt( common / (onlyUserB + common)) AS similarity

            MERGE (userA)-[s:SIMILARITY]-(userB)
            SET s.interests = similarity
            RETURN similarity
        ";

        $query = new Query($this->client, $template, $parameters);

        $result = $query->getResultSet();

        $similarity = 0;
        if ($result->count() > 0) {
            /* @var $row Row */
            $row = $result->current();
            /* @var $node Node */
            $similarity = $row->offsetGet('similarity');
        }

        return $similarity;
    }
}