<?php

namespace Model\User\Similarity;

use Event\SimilarityEvent;
use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;
use Model\Popularity\PopularityManager;
use Model\User\ContentPaginatedModel;
use Model\User\Group\GroupModel;
use Model\User\ProfileModel;
use Model\User\QuestionPaginatedModel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class SimilarityModel
{
    const numberOfSecondsToCache = 30;
    const ALL = 1;
    const INTERESTS = 2;
    const QUESTIONS = 3;
    const SKILLS = 4;
    const MAX_SIMILARITIES = 1000;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var PopularityManager
     */
    protected $popularityManager;

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

    protected $groupModel;

    public function __construct(
        EventDispatcher $dispatcher,
        GraphManager $gm,
        PopularityManager $popularityManager,
        QuestionPaginatedModel $questionPaginatedModel,
        ContentPaginatedModel $contentPaginatedModel,
        ProfileModel $profileModel,
        GroupModel $groupModel
    )
    {
        $this->dispatcher = $dispatcher;
        $this->gm = $gm;
        $this->popularityManager = $popularityManager;
        $this->questionPaginatedModel = $questionPaginatedModel;
        $this->contentPaginatedModel = $contentPaginatedModel;
        $this->profileModel = $profileModel;
        $this->groupModel = $groupModel;
    }

    /**
     * Recalculates outdated similarities and returns total
     * @param $idA
     * @param $idB
     * @return array
     */
    public function getSimilarity($idA, $idB)
    {
        $idA = (integer)$idA;
        $idB = (integer)$idB;

        $similarity = $this->getCurrentSimilarity($idA, $idB);

        $minTimestampForCache = time() - self::numberOfSecondsToCache;
        $hasToRecalculateQuestions = ($similarity['questionsUpdated'] / 1000) < $minTimestampForCache;
        $hasToRecalculateContent = ($similarity['interestsUpdated'] / 1000) < $minTimestampForCache;
        $hasToRecalculateSkills = ($similarity['skillsUpdated'] / 1000) < $minTimestampForCache;

        if ($hasToRecalculateQuestions || $hasToRecalculateContent || $hasToRecalculateSkills) {
            if ($hasToRecalculateQuestions) {
                $this->calculateSimilarityByQuestions($idA, $idB);
            }
            if ($hasToRecalculateContent) {
                $this->calculateSimilarityByInterests($idA, $idB);
            }
            if ($hasToRecalculateSkills) {
                $this->calculateSimilarityBySkills($idA, $idB);
            }

            $similarity = $this->getCurrentSimilarity($idA, $idB);
            $this->dispatcher->dispatch(\AppEvents::SIMILARITY_UPDATED, new SimilarityEvent($idA, $idB, $similarity['similarity']));
        }

        return $this->returnSimilarity($similarity, $idA, $idB);
    }

    /**
     * Recalculates chosen similarity and returns it
     * @param $category
     * @param $idA
     * @param $idB
     * @return array
     */
    public function getSimilarityBy($category, $idA, $idB)
    {
        switch ($category) {
            case static::ALL:
                $this->calculateSimilarityByInterests($idA, $idB);
                $this->calculateSimilarityByQuestions($idA, $idB);
                $this->calculateSimilarityBySkills($idA, $idB);
                break;
            case static::INTERESTS:
                $this->calculateSimilarityByInterests($idA, $idB);
                break;
            case static::QUESTIONS:
                $this->calculateSimilarityByQuestions($idA, $idB);
                break;
            case static::SKILLS:
                $this->calculateSimilarityBySkills($idA, $idB);
                break;
            default:
                //TODO: throw InvalidArgumentException
                return array();
        }
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
                's.skills AS skills',
                's.similarity AS similarity',
                'CASE WHEN EXISTS(s.questionsUpdated) THEN s.questionsUpdated ELSE 0 END AS questionsUpdated',
                'CASE WHEN EXISTS(s.interestsUpdated) THEN s.interestsUpdated ELSE 0 END AS interestsUpdated',
                'CASE WHEN EXISTS(s.skillsUpdated) THEN s.skillsUpdated ELSE 0 END AS skillsUpdated',
                'CASE WHEN EXISTS(s.similarityUpdated) THEN s.similarityUpdated ELSE 0 END AS similarityUpdated'
            )
            ->returns('questions, interests, skills, similarity, questionsUpdated, interestsUpdated, skillsUpdated, similarityUpdated');

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
            'skills' => 0,
            'similarity' => 0,
            'questionsUpdated' => 0,
            'interestsUpdated' => 0,
            'skillsUpdated' => 0,
            'similarityUpdated' => 0,
        );
        if ($result->count() > 0) {
            /* @var $row Row */
            $row = $result->current();
            $similarity = $this->buildSimilarity($row);

        }

        return $similarity;
    }

    /**
     * @param $id
     * @param bool $includeGhost
     * @return \array[]
     * @throws \Model\Neo4j\Neo4jException
     */
    public function getAllCurrentByUser($id, $includeGhost = true)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(u:User{qnoow_id: {id} })')
            ->with('u')
            ->limit(1);
        $qb->setParameter('id', (integer)$id);

        //Only with interests for analyzing purposes. May be refactored into different methods for different similarities.
        $qb->match('(u)-[s:SIMILARITY]->(u2:User)')
            ->where('s.interests > 0');
        $qb->with('u', 'u2', 's');
        if (!$includeGhost) {
            $qb->where('NOT (u2:GhostUser)');
        };
        $qb->with('u', 'u2', 's');

        $qb->optionalMatch('(u)-[:LIKES]->(l:Link)')
            ->optionalMatch('(l)-[:HAS_POPULARITY]-(popularity)')
            ->with('u', 'u2', 's', 'count(l) AS countA, sum(popularity.popularity) AS popularityA');
        $qb->optionalMatch('(u2)-[:LIKES]->(l:Link)')
            ->optionalMatch('(l)-[:HAS_POPULARITY]-(popularity)')
            ->with('u', 'u2', 's', 'countA', 'popularityA', 'count(l) as countB', 'sum(popularity.popularity) AS popularityB');

        $qb->optionalMatch('(u)-[:LIKES]->(l:Link)<-[:LIKES]-(u2)')
            ->optionalMatch('(l)-[:HAS_POPULARITY]-(popularity:Popularity)')
            ->with('u', 'u2', 's', 'countA, popularityA, countB, popularityB', 'count(l) AS countCommon', 'sum(popularity.unpopularity) AS unpopularityCommon', 'sum(popularity.popularity) AS popularityCommon');
        $qb->with('countCommon', 'unpopularityCommon',
            'countA - countCommon AS countOnlyA', 'countB - countCommon AS countOnlyB',
            'popularityA - popularityCommon AS popularityOnlyA', 'popularityB - popularityCommon AS popularityOnlyB',
            's.questions AS questions',
            's.interests AS interests',
            's.skills AS skills',
            's.similarity AS similarity',
            'CASE WHEN EXISTS(s.questionsUpdated) THEN s.questionsUpdated ELSE 0 END AS questionsUpdated',
            'CASE WHEN EXISTS(s.interestsUpdated) THEN s.interestsUpdated ELSE 0 END AS interestsUpdated',
            'CASE WHEN EXISTS(s.skillsUpdated) THEN s.skillsUpdated ELSE 0 END AS skillsUpdated',
            'CASE WHEN EXISTS(s.similarityUpdated) THEN s.similarityUpdated ELSE 0 END AS similarityUpdated'
        )
            ->returns('questions, interests, skills, similarity, questionsUpdated, interestsUpdated, skillsUpdated, similarityUpdated, 
                        countCommon, unpopularityCommon, countOnlyA, countOnlyB, popularityOnlyA, popularityOnlyB');

        $result = $qb->getQuery()->getResultSet();

        $similarities = array();
        $popularityData = array();
        foreach ($result as $row) {
            $similarities[] = $this->buildSimilarity($row);
            $popularityData[] = array(
                'countCommon' => $row->offsetGet('countCommon'),
                'unpopularityCommon' => $row->offsetGet('unpopularityCommon'),
                'countOnlyA' => $row->offsetGet('countOnlyA'),
                'countOnlyB' => $row->offsetGet('countOnlyB'),
                'popularityOnlyA' => $row->offsetGet('popularityOnlyA'),
                'popularityOnlyB' => $row->offsetGet('popularityOnlyB'),
            );
        }

        return array(
            'similarities' => $similarities,
            'popularityData' => $popularityData,
        );
    }

    public function recalculateSimilaritiesByQuestions($userId, $limit = 800)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(u:User{qnoow_id: {userId}})')
            ->setParameter('userId', (integer)$userId)
            ->with('u')
            ->limit(1);

        $qb->match('(u)-[:ANSWERS]->(a:Answer)-[:IS_ANSWER_OF]->(q:Question)')
            ->with('u', 'collect(a) AS answers', 'collect(q) AS questions');
        $qb->match('(u)-[s:SIMILARITY]-(u2)')
            ->where('EXISTS(s.questionsUpdated)','s.questionsUpdated < timestamp() - {updateLimit}')
            ->setParameter('updateLimit', 1000*self::numberOfSecondsToCache)
            ->with('u', 'answers','questions','u2')
            ->orderBy('1-s.questions ASC') // s.questions DESC would put NULL values first
            ->limit('{limit}')
            ->setParameter('limit', $limit);

        $qb->match('(u2)-[:ANSWERS]-(a2)-[:IS_ANSWER_OF]->(q2:Question)')
            ->with('u', 'u2', 'a2', 'q2')
            ->where('a2 IN answers', 'q2 IN questions')
            ->with('u', 'u2', 'collect(a2) AS answers2', 'collect(q2) AS questions2')
            ->with('u', 'u2', 'size(answers2) AS RI', 'size(questions2) AS PC')
            //Same as $this->calculateSimilarityByQuestions from here
            ->with('u', 'u2', 'toFloat(RI) AS RI', 'toFloat(PC) AS PC')
            ->with('u, u2, CASE WHEN PC <= 0 THEN toFloat(0) ELSE RI/PC - 1/PC END AS similarity')
            ->with('u, u2, CASE WHEN similarity < 0 THEN toFloat(0) ELSE similarity END AS similarity');

        $qb
            ->merge('(u)-[s:SIMILARITY]-(u2)')
            ->set(
                's.questions = similarity',
                's.interests = CASE WHEN EXISTS(s.interests) THEN s.interests ELSE 0 END',
                's.skills = CASE WHEN EXISTS(s.skills) THEN s.skills ELSE 0 END',
                's.questionsUpdated = timestamp()',
                's.similarityUpdated = timestamp()'
            )
            ->returns('similarity');

        $qb->getQuery()->getResultSet();
    }

    public function recalculateSimilaritiesByInterests($userId, $limit = 800)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(u:User{qnoow_id: {userId}})')
            ->setParameter('userId', (integer)$userId)
            ->with('u')
            ->limit(1);

        $qb->match('(u)-[:LIKES]->(l:Link)-[:HAS_POPULARITY]->(p:Popularity)')
            ->with('u', 'sum(p.popularity) AS popularity');

        $qb->match('(u)-[s:SIMILARITY]->(u2:User)')
            ->where('EXISTS(s.interestsUpdated)','s.interestsUpdated < timestamp() - {updateLimit}')
            ->setParameter('updateLimit', 1000*self::numberOfSecondsToCache)
            ->with('u', 'popularity', 'u2')
            ->orderBy('1-s.interests ASC')
            ->limit('{limit}')
            ->setParameter('limit', (integer)$limit);

        $qb->match('(u2)-[:LIKES]->(:Link)-[:HAS_POPULARITY]->(p:Popularity)')
            ->with('u', 'popularity', 'u2', 'sum(p.popularity) AS popularity2');

        $qb->match('(u2)-[:LIKES]->(l:Link)<-[:LIKES]-(u)')
            ->match('(l)-[:HAS_POPULARITY]->(p:Popularity)')
            ->with('u', 'popularity', 'u2', 'popularity2',
                'CASE WHEN count(l) > 4 THEN true ELSE false END AS valid',
                'sum(p.popularity) AS popularityCommon',
                'sum(p.unpopularity) AS unpopularityCommon');

        $qb->with('u', 'u2', 'valid', 'unpopularityCommon', 'popularity - popularityCommon AS popularity1', 'popularity2 - popularityCommon AS popularity2' )
            ->with(' u, u2, valid, unpopularityCommon, popularity1, popularity2');

        $qb
            ->with('u, u2, valid, sqrt( unpopularityCommon / (popularity1 + unpopularityCommon)) * sqrt( unpopularityCommon / (popularity2 + unpopularityCommon)) AS similarity')
            ->with('u', 'u2', 'CASE WHEN valid THEN similarity ELSE 0 END AS similarity');

        $qb
            ->merge('(u)-[s:SIMILARITY]-(u2)')
            ->set(
                's.interests = similarity',
                's.questions = CASE WHEN EXISTS(s.questions) THEN s.questions ELSE 0 END',
                's.skills = CASE WHEN EXISTS(s.skills) THEN s.skills ELSE 0 END',
                's.interestsUpdated = timestamp()',
                's.similarityUpdated = timestamp()'
            )
            ->returns('similarity');

        $result = $qb->getQuery()->getResultSet();
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
                's.interests = CASE WHEN EXISTS(s.interests) THEN s.interests ELSE 0 END',
                's.skills = CASE WHEN EXISTS(s.skills) THEN s.skills ELSE 0 END',
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
            $similarity = $row->offsetGet('similarity');
        }

        return $similarity;
    }

    private function calculateSimilarityByInterests($idA, $idB)
    {
        $this->popularityManager->updatePopularityByUser($idA);
        $this->popularityManager->updatePopularityByUser($idB);

        $qb = $this->gm->createQueryBuilder();
        $qb
            ->match('(userA:User {qnoow_id: { idA } }), (userB:User {qnoow_id: { idB } })')
            ->where('userA <> userB')
            ->optionalMatch('(userA)-[:LIKES]-(l:Link)-[:LIKES]-(userB)')
            ->optionalMatch('(l)-[:HAS_POPULARITY]-(popularity:Popularity)')
            ->where('EXISTS(l.unpopularity) OR EXISTS(popularity.unpopularity)')
            ->with('userA, userB, COUNT(DISTINCT l) AS numberCommonContent, SUM(l.unpopularity) + SUM(popularity.unpopularity) AS common')
            ->with('userA', 'userB', 'CASE WHEN numberCommonContent > 4 THEN true ELSE false END AS valid', 'common')
            ->with('userA', 'userB', 'valid', 'CASE WHEN valid THEN common ELSE 1 END AS common')//prevents divide by zero
            ->with('userA', 'userB', 'valid', 'common');

        $qb
            ->optionalMatch('(userA)-[:LIKES]-(l1:Link)')
            ->where('NOT (userB)-[:LIKES]->(l1)')
            ->with('userA', 'userB', 'valid', 'common, l1')
            ->optionalMatch('(l1)-[:HAS_POPULARITY]-(popularity:Popularity)')
            ->where('(EXISTS(l1.popularity) OR EXISTS(popularity.popularity))')
            ->with('userA, userB, valid, common, SUM(l1.popularity) + SUM(popularity.popularity) AS onlyUserA');

        $qb
            ->optionalMatch('(userB)-[:LIKES]-(l2:Link)')
            ->where('NOT (userA)-[:LIKES]->(l2)')
            ->with('userA', 'userB', 'valid', 'common, onlyUserA, l2')
            ->optionalMatch('(l2)-[:HAS_POPULARITY]-(popularity:Popularity)')
            ->where('EXISTS(l2.popularity) OR EXISTS(popularity.popularity)')
            ->with(' userA, userB, valid, common, onlyUserA, SUM(l2.popularity) + SUM(popularity.popularity) AS onlyUserB');

        $qb
            ->with('userA, userB, valid, sqrt( common / (onlyUserA + common)) * sqrt( common / (onlyUserB + common)) AS similarity')
            ->with('userA', 'userB', 'CASE WHEN valid THEN similarity ELSE 0 END AS similarity');

        $qb
            ->merge('(userA)-[s:SIMILARITY]-(userB)')
            ->set(
                's.interests = similarity',
                's.questions = CASE WHEN EXISTS(s.questions) THEN s.questions ELSE 0 END',
                's.skills = CASE WHEN EXISTS(s.skills) THEN s.skills ELSE 0 END',
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
            $similarity = $row->offsetGet('similarity');
        }
        return $similarity;
    }

    private function calculateSimilarityBySkills($idA, $idB)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb
            ->match('(userA:User {qnoow_id: { idA } }), (userB:User {qnoow_id: { idB } })')
            ->where('userA <> userB')
            ->optionalMatch('(userA)-[:SPEAKS_LANGUAGE]->(language:Language)<-[:SPEAKS_LANGUAGE]-(userB)')
            ->with('userA', 'userB', 'COUNT(DISTINCT language) as commonLanguages')
            ->optionalMatch('(userA)-[:HAS_SKILL]->(skill:Skill)<-[:HAS_SKILL]-(userB)')
            ->with('userA, userB, commonLanguages + COUNT(DISTINCT skill) AS common')
            ->with('userA', 'userB', 'common');

        $qb
            ->optionalMatch('(userA)-[:SPEAKS_LANGUAGE]->(language:Language)')
            ->where('NOT (userB)-[:SPEAKS_LANGUAGE]->(language)')
            ->with('userA', 'userB', 'common', 'COUNT(distinct language) AS languagesA')
            ->optionalMatch('(userA)-[:HAS_SKILL]->(skill:Skill)')
            ->where('NOT (userB)-[:HAS_SKILL]->(skill)')
            ->with('userA, userB, common, languagesA +  COUNT(distinct skill) AS onlyUserA');

        $qb
            ->optionalMatch('(userB)-[:SPEAKS_LANGUAGE]->(language:Language)')
            ->where('NOT (userA)-[:SPEAKS_LANGUAGE]->(language)')
            ->with('userA', 'userB', 'onlyUserA', 'common', 'COUNT(distinct language) AS languagesB')
            ->optionalMatch('(userB)-[:HAS_SKILL]->(skill:Skill)')
            ->where('NOT (userA)-[:HAS_SKILL]->(skill)')
            ->with('userA, userB, common, onlyUserA, languagesB +  COUNT(distinct skill) AS onlyUserB');

        $qb
            ->with('userA, userB, toFloat(common) AS common, toFloat(onlyUserA) as onlyUserA, toFloat(onlyUserB) as onlyUserB')
            ->with('userA, userB,  CASE 
                                        WHEN common=0 AND (onlyUserA = 0 OR onlyUserB = 0) THEN 0
                                        ELSE sqrt( common / (onlyUserA + common)) * sqrt( common / (onlyUserB + common))
                                    END AS similarity');

        $qb
            ->merge('(userA)-[s:SIMILARITY]-(userB)')
            ->set(
                's.skills = similarity',
                's.interests = CASE WHEN EXISTS(s.interests) THEN s.interests ELSE 0 END',
                's.questions = CASE WHEN EXISTS(s.questions) THEN s.questions ELSE 0 END',
                's.skillsUpdated = timestamp()',
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
            $similarity = $row->offsetGet('similarity');
        }
        return $similarity;
    }

    /**
     * Calculates averages and sets to database. To be called only from public "get" methods.
     * @param array $similarity
     * @param $idA
     * @param $idB
     * @return array
     */
    private function returnSimilarity(array $similarity, $idA, $idB)
    {
        $questionLimit = 0;
        $contentLimit = 10;
        $skillLimit = 0;

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

        $userAQuestions = $totalQuestionsA > $questionLimit;
        $userBQuestions = $totalQuestionsB > $questionLimit;
        $userALinks = $totalLinksA > $contentLimit;
        $userBLinks = $totalLinksB > $contentLimit;
        //To change when we implement countSkills
//        $userASkills = $totalSkillsA > $skillLimit;
//        $userBSkills = $totalSkillsA > $skillLimit;
        $userASkills = $similarity['skills'] > 0;
        $userBSkills = $similarity['skills'] > 0;


        //"Do not use questions if and only if any user has no questions and has more than 100 links"
        $questionsFactor = ((($userALinks || $userASkills) && !$userAQuestions) || (($userBLinks || $userBSkills) && !$userBQuestions)) ? 0 : 1;
        $contentsFactor = ($userALinks || $userAQuestions) && ($userBLinks || $userBQuestions) ? 1 : 0; //include questions to be consistent with previous behaviour
//        $skillsFactor = $userASkills & $userBSkills ? 1 : 0;
        $skillsFactor = $similarity['skills'] > 0 ? 1 : 0;

        $denominator = $questionsFactor + $contentsFactor + $skillsFactor;

        //TODO: Check why this is necessary NOW
        ////// PATCH TO FIX SIMILARITIES OF INVESTOR GROUPS //////////////////
        //0-------------------limitSimilarity-------1
        // TO
        //0---------newLimitSimilarity--------------1
        //Contracts/expands intervals to fix similarities
        $limitSimilarity = 0.85;
        $newLimitSimilarity = 0.4;
        $investorGroupIds = array(9507567);

        foreach ($investorGroupIds as $investorGroupId) {
            if ($this->groupModel->isUserFromGroup($investorGroupId, $idA) && $this->groupModel->isUserFromGroup($investorGroupId, $idB)) {
                if ($similarity['interests'] > $limitSimilarity) {
                    $similarity['interests'] = (($similarity['interests'] - $limitSimilarity) / (1 - $limitSimilarity)) * (1 - $newLimitSimilarity) + $newLimitSimilarity;
                } else {
                    $similarity['interests'] = (($similarity['interests']) / $limitSimilarity) * $newLimitSimilarity;
                }
            }
        }
        /////////////////////////////////////////////////////////////////////

        $similarity['similarity'] = $denominator == 0 ? 0 :
            (($similarity['interests'] * $contentsFactor + $similarity['questions'] * $questionsFactor + $similarity['skills'] * $skillsFactor)
                / ($denominator)
            );

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

    private function buildSimilarity(Row $row)
    {
        $similarity = array();
        $similarity['questions'] = $row->offsetExists('questions') ? $row->offsetGet('questions') : 0;
        $similarity['interests'] = $row->offsetExists('interests') ? $row->offsetGet('interests') : 0;
        $similarity['skills'] = $row->offsetExists('skills') ? $row->offsetGet('skills') : 0;
        $similarity['similarity'] = $row->offsetExists('similarity') ? $row->offsetGet('similarity') : 0;
        $similarity['questionsUpdated'] = $row->offsetExists('questionsUpdated') ? $row->offsetGet('questionsUpdated') : 0;
        $similarity['interestsUpdated'] = $row->offsetExists('interestsUpdated') ? $row->offsetGet('interestsUpdated') : 0;
        $similarity['skillsUpdated'] = $row->offsetExists('skillsUpdated') ? $row->offsetGet('skillsUpdated') : 0;
        $similarity['similarityUpdated'] = $row->offsetExists('similarityUpdated') ? $row->offsetGet('similarityUpdated') : 0;

        return $similarity;
    }
}