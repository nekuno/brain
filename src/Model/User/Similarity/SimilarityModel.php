<?php

namespace Model\User\Similarity;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;
use Model\LinkModel;
use Model\User\ContentPaginatedModel;
use Model\User\ProfileModel;
use Model\User\QuestionPaginatedModel;


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

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var LinkModel
     */
    protected $linkModel;

    /**
     * @var QuestionPaginatedModel
     */
    protected $questionPaginatedModel;

    /**
     * @var ContentPaginatedModel
     */
    protected $contentPaginatedModel;

    /**
     * @var ProfileModel
     */
    protected $profileModel;

    public function __construct(Client $client,
                                GraphManager $gm,
                                LinkModel $linkModel,
                                QuestionPaginatedModel $questionPaginatedModel,
                                ContentPaginatedModel $contentPaginatedModel,
                                ProfileModel $profileModel)
    {
        $this->client = $client;
        $this->gm = $gm;
        $this->linkModel = $linkModel;
        $this->questionPaginatedModel = $questionPaginatedModel;
        $this->contentPaginatedModel = $contentPaginatedModel;
        $this->profileModel = $profileModel;
    }

    public function getSimilarity($idA, $idB)
    {
        $idA = (integer)$idA;
        $idB = (integer)$idB;

        $similarity = $this->getCurrentSimilarity($idA, $idB);

        $minTimestampForCache  = time() - self::numberOfSecondsToCache;
        $hasToRecalculateQuestions = ($similarity['questionsUpdated'] / 1000) < $minTimestampForCache;
        $hasToRecalculateContent = ($similarity['interestsUpdated'] / 1000) < $minTimestampForCache;

        if ($hasToRecalculateQuestions || $hasToRecalculateContent) {
            if ($hasToRecalculateQuestions) {
                $this->calculateSimilarityByQuestions($idA, $idB);
            }
            if ($hasToRecalculateContent) {
                $this->calculateSimilarityByInterests($idA, $idB);
            }

            $similarity = $this->getCurrentSimilarity($idA, $idB);
        }

        return $similarity;
    }

    private function getCurrentSimilarity($idA, $idB)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb
            ->match('(userA:User {qnoow_id: { idA } }), (userB:User {qnoow_id: { idB } })')
            ->match('(userA)-[s:SIMILARITY]-(userB)')
            ->with(
                's.questions AS questions',
                's.interests AS interests',
                's.similarity AS similarity',
                'CASE WHEN HAS(s.questionsUpdated) THEN s.questionsUpdated ELSE 0 END AS questionsUpdated',
                'CASE WHEN HAS(s.interestsUpdated) THEN s.interestsUpdated ELSE 0 END AS interestsUpdated',
                'CASE WHEN HAS(s.similarityUpdated) THEN s.similarityUpdated ELSE 0 END AS similarityUpdated'
            )
            ->returns('questions, interests, similarity, questionsUpdated, interestsUpdated, similarityUpdated');

        $qb->setParameters(
            array(
                'idA' => (integer)$idA,
                'idB' => (integer)$idB,
            )
        );

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $similarity = array(
            'questions' => 0,
            'interests' => 0,
            'similarity' => 0,
            'questionsUpdated' => 0,
            'interestsUpdated' => 0,
            'similarityUpdated' => 0,
        );
        if ($result->count() > 0) {
            /* @var $row Row */
            $row = $result->current();
            /* @var $node Node */
            $similarity['questions'] = $row->offsetGet('questions');
            $similarity['interests'] = $row->offsetGet('interests');
            $similarity['similarity'] = $row->offsetGet('similarity');
            $similarity['questionsUpdated'] = $row->offsetGet('questionsUpdated');
            $similarity['interestsUpdated'] = $row->offsetGet('interestsUpdated');
            $similarity['similarityUpdated'] = $row->offsetGet('similarityUpdated');
        }

        $similarity = $this->returnSimilarity($similarity, $idA, $idB);

        return $similarity;
    }

    public function calculateSimilarityByQuestions($idA, $idB)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb
            ->match('(userA:User {qnoow_id: { idA } }), (userB:User {qnoow_id: { idB } })')
            ->where('userA <> userB')
            ->match('(userA)-[:ANSWERS]-(answerA:Answer)-[:IS_ANSWER_OF]-(q:Question)')
            ->match('(userB)-[:ANSWERS]-(answerB:Answer)-[:IS_ANSWER_OF]-(q)')
            ->with('userA, userB, q, CASE WHEN answerA = answerB THEN 1 ELSE 0 END AS equal')
            ->with('userA, userB, toFloat(COUNT(q)) AS PC, toFloat(SUM(equal)) AS RI')
            ->with('userA, userB, CASE WHEN PC <= 0 THEN toFloat(0) ELSE RI/PC - 1/PC END AS similarity')
            ->with('userA, userB, CASE WHEN similarity < 0 THEN toFloat(0) ELSE similarity END AS similarity');

        $qb
            ->merge('(userA)-[s:SIMILARITY]-(userB)')
            ->set('s.questions = similarity',
                's.interests = CASE WHEN HAS(s.interests) THEN s.interests ELSE 0 END',
                's.similarity = (s.questions + s.interests) / 2',
                's.questionsUpdated = timestamp()',
                's.similarityUpdated = timestamp()'
            )
            ->returns('similarity');

        $qb->setParameters(
            array(
                'idA' => (integer)$idA,
                'idB' => (integer)$idB,
            )
        );

        $query = $qb->getQuery();
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

    public function calculateSimilarityByInterests($idA, $idB)
    {
        $this->linkModel->updatePopularity(array('userId' => $idA));
        $this->linkModel->updatePopularity(array('userId' => $idB));

        $qb = $this->gm->createQueryBuilder();
        $qb
            ->match('(userA:User {qnoow_id: { idA } }), (userB:User {qnoow_id: { idB } })')
            ->where('userA <> userB')
            ->match('(userA)-[:LIKES]-(l:Link)-[:LIKES]-(userB)')
            ->where('HAS(l.unpopularity)')
            ->with('userA, userB, COUNT(DISTINCT l) AS numberCommonContent, SUM(l.unpopularity) AS common')
            ->where('numberCommonContent > 4')
            ->with('userA, userB, common');

        $qb
            ->optionalMatch('(userA)-[:LIKES]-(l1:Link)')
            ->where('NOT (userB)-[:LIKES]->(l1) AND HAS(l1.popularity)')
            ->with('userA, userB, common, SUM(l1.popularity) AS onlyUserA');

        $qb
            ->optionalMatch('(userB)-[:LIKES]-(l2:Link)')
            ->where('NOT (userA)-[:LIKES]->(l2) AND HAS(l2.popularity)')
            ->with(' userA, userB, common, onlyUserA, SUM(l2.popularity) AS onlyUserB');

        $qb
            ->with('userA, userB, sqrt( common / (onlyUserA + common)) * sqrt( common / (onlyUserB + common)) AS similarity');

        $qb
            ->merge('(userA)-[s:SIMILARITY]-(userB)')
            ->set(
                's.interests = similarity',
                's.questions = CASE WHEN HAS(s.questions) THEN s.questions ELSE 0 END',
                's.similarity = (s.questions + s.interests) / 2',
                's.interestsUpdated = timestamp()',
                's.similarityUpdated = timestamp()'
            )
            ->returns('similarity');

        $qb->setParameters(
            array(
                'idA' => (integer)$idA,
                'idB' => (integer)$idB,
            )
        );

        $query = $qb->getQuery();
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

    private function returnSimilarity($similarity, $idA, $idB)
    {
        $questionLimit = 0;
        $contentLimit = 1000;

        $profileA = $this->profileModel->getById($idA);
        $profileB = $this->profileModel->getById($idB);
        $interfaceLanguageA = isset($profileA['interfaceLanguage']) ? $profileA['interfaceLanguage'] : 'es';
        $interfaceLanguageB = isset($profileB['interfaceLanguage']) ? $profileB['interfaceLanguage'] : 'es';
        $totalLinksA = $this->contentPaginatedModel->countTotal(array('id' => $idA));
        $totalLinksB = $this->contentPaginatedModel->countTotal(array('id' => $idB));

        $totalQuestionsA = $this->questionPaginatedModel->countTotal(array(
            'id' => $idA,
            'locale' => $interfaceLanguageA));
        $totalQuestionsB = $this->questionPaginatedModel->countTotal(array(
            'id' => $idB,
            'locale' => $interfaceLanguageB));

        if (($totalLinksA >= $contentLimit && $totalQuestionsA <= $questionLimit)
            || ($totalLinksB >= $contentLimit && $totalQuestionsB <= $questionLimit)
        ) {
            $similarity['similarity'] = $similarity['interests'];
            $this->setSimilarity($idA, $idB, $similarity['interests']);
        }

        return $similarity;
    }

    private function setSimilarity($idA, $idB, $similarity)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->setParameters(array(
            'idA' => $idA,
            'idB' => $idB,
            'similarity' => $similarity,
        ));

        $qb->match('(ua:User{qnoow_id:{idA}})', '(ub:User{qnoow_id:{idB}})')
            ->merge('(ua)-[s:SIMILARITY]-(ub)')
            ->set('s.similarity = {similarity}');

        $qb->getQuery()->getResultSet();
    }
}