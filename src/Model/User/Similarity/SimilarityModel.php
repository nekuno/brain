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
    const numberOfSecondsToCache = 86400;

    /**
     * @var Client
     */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getSimilarity($idA, $idB)
    {
        $currentSimilarity = $this->getCurrentSimilarity($idA, $idB);

        $minTimestampForCache  = time() - self::numberOfSecondsToCache;
        $hasToRecalculateQuestions = ($currentSimilarity['questionsUpdated'] / 1000) < $minTimestampForCache;
        $hasToRecalculateContent = ($currentSimilarity['interestsUpdated'] / 1000) < $minTimestampForCache;

        $similarity = $currentSimilarity['value'];
        if ($hasToRecalculateQuestions || $hasToRecalculateContent) {
            if ($hasToRecalculateQuestions) {
                $this->calculateSimilarityByQuestions($idA, $idB);
            }
            if ($hasToRecalculateQuestions) {
                $this->calculateSimilarityByInterests($idA, $idB);
            }

            $currentSimilarity = $this->getCurrentSimilarity($idA, $idB);
            $similarity = $currentSimilarity['value'];
        }

        return $similarity;
    }

    private function getCurrentSimilarity($idA, $idB) {
        $parameters = array(
          'idA' => (integer)$idA,
          'idB' => (integer)$idB,
        );

        $template = "
            MATCH (userA:User {qnoow_id: {idA}}), (userB:User {qnoow_id: {idB}})
            MATCH (userA)-[s:SIMILARITY]-(userB)
            WITH CASE WHEN HAS(s.questions) THEN s.questions ELSE 0 END AS questions,
                 CASE WHEN HAS(s.interests) THEN s.interests ELSE 0 END AS interests,
                 CASE WHEN HAS(s.questionsUpdated) THEN s.questionsUpdated ELSE 0 END AS questionsUpdated,
                 CASE WHEN HAS(s.interestsUpdated) THEN s.interestsUpdated ELSE 0 END AS interestsUpdated
            RETURN (questions + interests) / 2 AS similarity, questionsUpdated, interestsUpdated
        ";

        $query = new Query($this->client, $template, $parameters);

        $result = $query->getResultSet();

        $similarity = array(
            'value' => 0,
            'questionsUpdated' => 0,
            'interestsUpdated' => 0,
        );
        if ($result->count() > 0) {
            /* @var $row Row */
            $row = $result->current();
            /* @var $node Node */
            $similarity['value'] = $row->offsetGet('similarity');
            $similarity['questionsUpdated']  = $row->offsetGet('questionsUpdated');
            $similarity['interestsUpdated']  = $row->offsetGet('interestsUpdated');
        }

        return $similarity;
    }

    private function calculateSimilarityByQuestions($idA, $idB)
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
            SET s.questions = similarity, s.questionsUpdated = timestamp()
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

    private function calculateSimilarityByInterests($idA, $idB)
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
            SET s.interests = similarity, s.interestsUpdated = timestamp()
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