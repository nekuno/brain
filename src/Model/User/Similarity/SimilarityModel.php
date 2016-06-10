<?php

namespace Model\User\Similarity;

use Event\SimilarityEvent;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;
use Model\Popularity\PopularityManager;
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
    const numberOfSecondsToCache = 0;
    const ALL = 1;
    const INTERESTS = 2;
    const QUESTIONS = 3;

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

    public function __construct(
        EventDispatcher $dispatcher,
        GraphManager $gm,
        PopularityManager $popularityManager,
        QuestionPaginatedModel $questionPaginatedModel,
        ContentPaginatedModel $contentPaginatedModel,
        ProfileModel $profileModel
    ) {
        $this->dispatcher = $dispatcher;
        $this->gm = $gm;
        $this->popularityManager = $popularityManager;
        $this->questionPaginatedModel = $questionPaginatedModel;
        $this->contentPaginatedModel = $contentPaginatedModel;
        $this->profileModel = $profileModel;
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

    /**
     * Recalculates chosen similarity and returns it
     * @param $category
     * @param $idA
     * @param $idB
     * @return array
     */
    public function getSimilarityBy($category, $idA, $idB){
        switch($category){
            case static::ALL:
                $this->calculateSimilarityByInterests($idA, $idB);
                $this->calculateSimilarityByQuestions($idA, $idB);
                break;
            case static::INTERESTS:
                $this->calculateSimilarityByInterests($idA, $idB);
                break;
            case static::QUESTIONS:
                $this->calculateSimilarityByQuestions($idA, $idB);
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
                's.similarity AS similarity',
                'CASE WHEN EXISTS(s.questionsUpdated) THEN s.questionsUpdated ELSE 0 END AS questionsUpdated',
                'CASE WHEN EXISTS(s.interestsUpdated) THEN s.interestsUpdated ELSE 0 END AS interestsUpdated',
                'CASE WHEN EXISTS(s.similarityUpdated) THEN s.similarityUpdated ELSE 0 END AS similarityUpdated'
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
                's.interests = CASE WHEN EXISTS(s.interests) THEN s.interests ELSE 0 END',
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
            ->with('userA, userB, 0 AS numberCommonContent0, 0 AS common0, 0 AS onlyUserA0, 0 AS onlyUserB0');

        //needed to avoid late semantic checking error
        $counter = 0;
        foreach (PopularityManager::getPopularOptions() as $option){
            $qb->optionalMatch('(userA)-[:'.$option['type'].']-(l:'.$option['label'].')-[:'.$option['type'].']-(userB)')
                ->optionalMatch('(l)-[:HAS_POPULARITY]-(popularity:Popularity)')
                ->where('EXISTS(l.unpopularity) OR EXISTS(popularity.unpopularity)')
                ->with('userA, userB, numberCommonContent'.($counter).' + COUNT(DISTINCT l) AS numberCommonContent'.($counter+1).', 
                        common'.($counter).' + SUM(l.unpopularity) + SUM(popularity.unpopularity) AS common'.($counter+1).', onlyUserA'.($counter).', onlyUserB'.($counter).'')
                ->with('userA', 'userB', 'numberCommonContent'.($counter+1).'', 'common'.($counter+1).'', 'onlyUserA'.($counter).'', 'onlyUserB'.($counter).'');

            $qb
                ->optionalMatch('(userA)-[:'.$option['type'].']-(l1:'.$option['label'].')')
                ->where('NOT (userB)-[:'.$option['type'].']->(l1)')
                ->with('userA', 'userB', 'numberCommonContent'.($counter+1).'', 'common'.($counter+1).'', 'onlyUserA'.($counter).'', 'onlyUserB'.($counter).'', 'l1')
                ->optionalMatch('(l1)-[:HAS_POPULARITY]-(popularity:Popularity)')
                ->where('(EXISTS(l1.popularity) OR EXISTS(popularity.popularity))')
                ->with('userA, userB, numberCommonContent'.($counter+1).', common'.($counter+1).',
                            onlyUserA'.($counter).' + SUM(l1.popularity) + SUM(popularity.popularity) AS onlyUserA'.($counter+1).'', 'onlyUserB'.($counter).'');

            $qb
                ->optionalMatch('(userB)-[:'.$option['type'].']-(l2:'.$option['label'].')')
                ->where('NOT (userA)-[:'.$option['type'].']->(l2)')
                ->with('userA', 'userB', 'numberCommonContent'.($counter+1).'', 'common'.($counter+1).'', 'onlyUserA'.($counter+1).'', 'onlyUserB'.($counter).'', 'l2')
                ->optionalMatch('(l2)-[:HAS_POPULARITY]-(popularity:Popularity)')
                ->where('(EXISTS(l2.popularity) OR EXISTS(popularity.popularity))')
                ->with('userA, userB, numberCommonContent'.($counter+1).', common'.($counter+1).', onlyUserA'.($counter+1).',
                        onlyUserB'.($counter).' + SUM(l2.popularity) + SUM(popularity.popularity) AS onlyUserB'.($counter+1).'');
            $counter++;
        }

        $qb
            ->with('userA, userB, CASE WHEN numberCommonContent'.($counter).' > 4 THEN true ELSE false END AS valid, common'.($counter).', onlyUserA'.($counter).', onlyUserB'.($counter).'')
            ->with('userA, userB, valid, CASE WHEN valid THEN common'.($counter).' ELSE 1 END AS common, onlyUserA'.($counter).', onlyUserB'.($counter).'') //prevents divide by zero
            ->with('userA, userB, valid, sqrt( common / (onlyUserA'.($counter).' + common)) * sqrt( common / (onlyUserB'.($counter).' + common)) AS similarity')
            ->with('userA', 'userB', 'CASE WHEN valid THEN similarity ELSE 0 END AS similarity');

        $qb
            ->merge('(userA)-[s:SIMILARITY]-(userB)')
            ->set(
                's.interests = similarity',
                's.questions = CASE WHEN EXISTS(s.questions) THEN s.questions ELSE 0 END',
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

        $userAQuestions = $totalQuestionsA > $questionLimit;
        $userBQuestions = $totalQuestionsB > $questionLimit;
        $userALinks = $totalLinksA > $contentLimit;
        $userBLinks = $totalLinksB > $contentLimit;

        //"Do not use questions if and only if any user has no questions and has more than 100 links"
        $questionsFactor = ( (($userALinks) && !$userAQuestions) || ( ($userBLinks) && !$userBQuestions)) ? 0 : 1;
        $contentsFactor = ($userALinks || $userAQuestions) && ($userBLinks || $userBQuestions) ? 1 : 0; //include questions to be consistent with previous behaviour

        $denominator = $questionsFactor + $contentsFactor;

        $similarity['similarity'] = $denominator == 0 ? 0 :
                                        ( ($similarity['interests'] * $contentsFactor + $similarity['questions'] * $questionsFactor + $similarity['skills'] * $skillsFactor)
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
}