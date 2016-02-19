<?php

namespace Model\User\Similarity;

use Event\SimilarityEvent;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;
use Model\LinkModel;
use Model\User\ContentPaginatedModel;
use Model\User\ProfileModel;
use Model\User\QuestionPaginatedModel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class SimilarityModel
{
    const numberOfSecondsToCache = 86400;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

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

    public function __construct(
        EventDispatcher $dispatcher,
        GraphManager $gm,
        LinkModel $linkModel,
        QuestionPaginatedModel $questionPaginatedModel,
        ContentPaginatedModel $contentPaginatedModel,
        ProfileModel $profileModel
    ) {
        $this->dispatcher = $dispatcher;
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

        $minTimestampForCache = time() - self::numberOfSecondsToCache;
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
            $this->dispatcher->dispatch(\AppEvents::SIMILARITY_UPDATED, new SimilarityEvent($idA, $idB, $similarity['similarity']));
        }

        return $this->returnSimilarity($similarity, $idA, $idB);
    }

    public function getSimilarityByInterests($idA, $idB)
    {
        $this->calculateSimilarityByInterests($idA, $idB);

        $similarity = $this->getCurrentSimilarity($idA, $idB);

        $this->dispatcher->dispatch(\AppEvents::SIMILARITY_UPDATED, new SimilarityEvent($idA, $idB, $similarity['similarity']));

        return $this->returnSimilarity($similarity, $idA, $idB);
    }

    public function getSimilarityByQuestions($idA, $idB)
    {
        $this->calculateSimilarityByQuestions($idA, $idB);

        $similarity = $this->getCurrentSimilarity($idA, $idB);

        $this->dispatcher->dispatch(\AppEvents::SIMILARITY_UPDATED, new SimilarityEvent($idA, $idB, $similarity['similarity']));

        return $this->returnSimilarity($similarity, $idA, $idB);
    }

    public function getCurrentSimilarity($idA, $idB)
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
            $similarity['questions'] = $row->offsetExists('questions') ? $row->offsetGet('questions') : 0;
            $similarity['interests'] = $row->offsetExists('interests') ? $row->offsetGet('interests') : 0;
            $similarity['similarity'] = $row->offsetExists('similarity') ? $row->offsetGet('similarity') : 0;
            $similarity['questionsUpdated'] = $row->offsetExists('questionsUpdated') ? $row->offsetGet('questionsUpdated') : 0;
            $similarity['interestsUpdated'] = $row->offsetExists('interestsUpdated') ? $row->offsetGet('interestsUpdated') : 0;
            $similarity['similarityUpdated'] = $row->offsetExists('similarityUpdated') ? $row->offsetGet('similarityUpdated') : 0;
        }

        return $similarity;
    }

    /**
     * Similarity By Questions = (equal answers -1)/ common questions
     * To get equal answers we match every answer answered by both users
     * To get common questions we match every questions answered by both users, even with different answers
     * @param $idA
     * @param $idB
     * @return array|int|mixed|null
     * @throws \Model\Neo4j\Neo4jException
     */
    private function calculateSimilarityByQuestions($idA, $idB)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb
            ->match('(userA:User {qnoow_id: { idA } }), (userB:User {qnoow_id: { idB } })')
            ->where('userA <> userB')
            /* optional match to allow cases with 0 coincidences and still set parameters */
            ->optionalMatch('(userA)-[:ANSWERS]-(answerA:Answer)-[:IS_ANSWER_OF]-(qa:Question)')
            ->optionalMatch('(userB)-[:ANSWERS]-(answerB:Answer)-[:IS_ANSWER_OF]-(qb:Question)')
            /* _equal variables are booleans for all purposes to count correctly */
            ->with('userA, userB,
                CASE WHEN qa = qb THEN 1 ELSE 0 END AS question_equal,
                CASE WHEN answerA = answerB THEN 1 ELSE 0 END AS answer_equal')
            ->with('userA, userB, toFloat(SUM(question_equal)) AS PC, toFloat(SUM(answer_equal)) AS RI')
            /* 1/PC correction is to account for errors */
            ->with('userA, userB, CASE WHEN PC <= 0 THEN toFloat(0) ELSE RI/PC - 1/PC END AS similarity')
            ->with('userA, userB, CASE WHEN similarity < 0 THEN toFloat(0) ELSE similarity END AS similarity');

        $qb
            ->merge('(userA)-[s:SIMILARITY]-(userB)')
            ->set(
                's.questions = similarity',
                's.interests = CASE WHEN HAS(s.interests) THEN s.interests ELSE 0 END',
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

    private function calculateSimilarityByInterests($idA, $idB)
    {
        $this->linkModel->updatePopularity(array('userId' => $idA));
        $this->linkModel->updatePopularity(array('userId' => $idB));

        $qb = $this->gm->createQueryBuilder();
        $qb
            ->match('(userA:User {qnoow_id: { idA } }), (userB:User {qnoow_id: { idB } })')
            ->where('userA <> userB')
            ->optionalMatch('(userA)-[:LIKES]-(l:Link)-[:LIKES]-(userB)')
            ->where('HAS(l.unpopularity)')
            ->with('userA, userB, COUNT(DISTINCT l) AS numberCommonContent, SUM(l.unpopularity) AS common')
            ->with('userA', 'userB', 'CASE WHEN numberCommonContent > 4 THEN true ELSE false END AS valid', 'common')
            ->with('userA', 'userB', 'valid', 'CASE WHEN valid THEN common ELSE 1 END AS common') //prevents divide by zero
            ->with('userA', 'userB','valid', 'common');

        $qb
            ->optionalMatch('(userA)-[:LIKES]-(l1:Link)')
            ->where('NOT (userB)-[:LIKES]->(l1) AND HAS(l1.popularity)')
            ->with('userA, userB, valid, common, SUM(l1.popularity) AS onlyUserA');

        $qb
            ->optionalMatch('(userB)-[:LIKES]-(l2:Link)')
            ->where('NOT (userA)-[:LIKES]->(l2) AND HAS(l2.popularity)')
            ->with(' userA, userB, valid, common, onlyUserA, SUM(l2.popularity) AS onlyUserB');

        $qb
            ->with('userA, userB, valid, sqrt( common / (onlyUserA + common)) * sqrt( common / (onlyUserB + common)) AS similarity')
            ->with('userA', 'userB', 'CASE WHEN valid THEN similarity ELSE 0 END AS similarity');

        $qb
            ->merge('(userA)-[s:SIMILARITY]-(userB)')
            ->set(
                's.interests = similarity',
                's.questions = CASE WHEN HAS(s.questions) THEN s.questions ELSE 0 END',
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

    /**
     * @param array $similarity
     * @param $idA
     * @param $idB
     * @return array
     */
    private function returnSimilarity(array $similarity, $idA, $idB)
    {
        $questionLimit = 0;
        $contentLimit = 100;

        try {
            $profileA = $this->profileModel->getById($idA);
            $profileB = $this->profileModel->getById($idB);
        } catch (NotFoundHttpException $e) {
            $profileA = array();
            $profileB = array();
        }

        $interfaceLanguageA = isset($profileA['interfaceLanguage']) ? $profileA['interfaceLanguage'] : 'es';
        $interfaceLanguageB = isset($profileB['interfaceLanguage']) ? $profileB['interfaceLanguage'] : 'es';
        $totalLinksA = $this->contentPaginatedModel->countTotal(array('id' => $idA));
        $totalLinksB = $this->contentPaginatedModel->countTotal(array('id' => $idB));

        $totalQuestionsA = $this->questionPaginatedModel->countTotal(
            array(
                'id' => $idA,
                'locale' => $interfaceLanguageA
            )
        );
        $totalQuestionsB = $this->questionPaginatedModel->countTotal(
            array(
                'id' => $idB,
                'locale' => $interfaceLanguageB
            )
        );

        if (($totalLinksA >= $contentLimit && $totalQuestionsA <= $questionLimit)
            || ($totalLinksB >= $contentLimit && $totalQuestionsB <= $questionLimit)
        ) {
            $similarity['similarity'] = $similarity['interests'];
        } else {
            $similarity['similarity'] = ($similarity['interests'] + $similarity['questions']) / 2;
        }

        $this->setSimilarity($idA, $idB, $similarity['similarity']);

        return $similarity;
    }

    private function setSimilarity($idA, $idB, $similarity)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->setParameters(
            array(
                'idA' => $idA,
                'idB' => $idB,
                'similarity' => $similarity,
            )
        );

        $qb->match('(ua:User{qnoow_id:{idA}})', '(ub:User{qnoow_id:{idB}})')
            ->merge('(ua)-[s:SIMILARITY]-(ub)')
            ->set('s.similarity = {similarity}');

        $qb->getQuery()->getResultSet();
    }
}