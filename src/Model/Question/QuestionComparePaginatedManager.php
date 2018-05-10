<?php

namespace Model\Question;

use Everyman\Neo4j\Query\ResultSet;
use Model\Neo4j\GraphManager;
use Paginator\PaginatedInterface;

use Everyman\Neo4j\Query\Row;
use Service\AnswerService;

class QuestionComparePaginatedManager implements PaginatedInterface
{
    protected $answerService;

    protected $questionModel;

    protected $graphManager;

    /**
     * @param GraphManager $graphManager
     * @param AnswerService $answerService
     * @param QuestionManager $questionModel
     */
    public function __construct(GraphManager $graphManager, AnswerService $answerService, QuestionManager $questionModel)
    {
        $this->graphManager = $graphManager;
        $this->answerService = $answerService;
        $this->questionModel = $questionModel;
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
        $qb = $this->graphManager->createQueryBuilder();

        $id = $filters['id'];
        $id2 = $filters['id2'];
        $locale = $filters['locale'];

        $showOnlyCommon = false;
        if (isset($filters['showOnlyCommon'])) {
            $showOnlyCommon = $filters['showOnlyCommon'];
        }

        $qb->match('(u:User), (u2:User)')
            ->where('u.qnoow_id = {userId} AND u2.qnoow_id = {userId2}')
            ->with('u', 'u2')
            ->limit(1);

        $qb->match('(u)-[ua:ANSWERS]->(answer:Answer)-[:IS_ANSWER_OF]->(question:Question)')
            ->where("EXISTS(answer.text_$locale)")
            ->with('u', 'u2', 'question', 'answer', 'ua');

        $qb->match('(u2)<-[:PROFILE_OF]-(:Profile)<-[:OPTION_OF]-(:Mode)<-[:INCLUDED_IN]-(:QuestionCategory)-[:CATEGORY_OF]->(question)');

        if ($showOnlyCommon) {
            $qb->match('(u2)-[ua2:ANSWERS]-(answer2:Answer)-[:IS_ANSWER_OF]-(question)');
        } else {
            $qb->optionalMatch('(u2)-[ua2:ANSWERS]-(answer2:Answer)-[:IS_ANSWER_OF]-(question)');
        }

        $qb->optionalMatch('(u)-[:ACCEPTS]-(acceptedAnswers:Answer)-[:IS_ANSWER_OF]-(question)');
        $qb->optionalMatch('(u)-[rate:RATES]-(question)');
        $qb->optionalMatch('(u2)-[:ACCEPTS]-(acceptedAnswers2:Answer)-[:IS_ANSWER_OF]-(question)');
        $qb->optionalMatch('(u2)-[rate2:RATES]-(question)');
        $qb->optionalMatch('(possible_answers:Answer)-[:IS_ANSWER_OF]-(question)');

        $qb->returns(
            'question',
            'rate2 IS NOT NULL AS isCommon',
            '{
                question: question,
                answer: answer,
                userAnswer: ua,
                rates: rate,
                isCommon: rate2 IS NOT NULL,
                answers: collect(distinct possible_answers),
                acceptedAnswers: collect(distinct acceptedAnswers)
            } as other_questions',
            '{
                question: question,
                answer: answer2,
                userAnswer: ua2,
                rates: rate2,
                answers: collect(distinct possible_answers),
                acceptedAnswers: collect(distinct acceptedAnswers2)
            } as own_questions'
        )
            ->orderBy('isCommon DESC', 'id(question)')
            ->skip('{offset}')
            ->limit('{limit}');

        $qb->setParameters(
            array(
                'userId' => (integer)$id,
                'userId2' => (integer)$id2,
                'offset' => (integer)$offset,
                'limit' => (integer)$limit
            )
        );

        $result = $qb->getQuery()->getResultSet();

        $own_questions_results = $this->buildQuestionResults($result, 'own_questions', $locale);
        if (!empty($own_questions_results)) {
            $own_questions_results['userId'] = $id2;
        }

        $other_questions_results = $this->buildQuestionResults($result, 'other_questions', $locale);
        if (!empty($other_questions_results)) {
            $other_questions_results['userId'] = $id;
        }

        $resultArray = array();
        $noResults = empty($other_questions_results);
        if (!$noResults)
        {
            $resultArray = array($other_questions_results, $own_questions_results);
        }

        return $resultArray;
    }

    private function buildQuestionResults(ResultSet $result, $questionsKey, $locale)
    {
        $questions_results = array();
        /* @var $row Row */
        foreach ($result as $row) {
            if ($row->offsetGet($questionsKey)->offsetExists('userAnswer')) {
                $questions = $row->offsetGet($questionsKey);
                $questionId = $questions->offsetGet('question')->getId();
                $questions_results['questions'][$questionId] = $this->answerService->build($questions, $locale);

                if ($questions->offsetExists('isCommon')) {
                    $questions_results['questions'][$questionId]['question']['isCommon'] = $questions->offsetGet('isCommon');
                }

                foreach ($questions_results['questions'] as $questionId => &$questionData) {
                    $registerModes = $this->questionModel->getRegisterModes($questionId);
                    $questionData['question']['registerModes'] = $registerModes;
                }
            }
        }

        return $questions_results;
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
        $id2 = $filters['id2'];
        $locale = $filters['locale'];

        $showOnlyCommon = false;
        if (isset($filters['showOnlyCommon'])) {
            $showOnlyCommon = $filters['showOnlyCommon'];
        }

        $params = array(
            'userId' => (integer)$id,
            'userId2' => (integer)$id2
        );

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(u:User), (u2:User)')
            ->where('u.qnoow_id = {userId} AND u2.qnoow_id = {userId2}')
            ->with('u', 'u2')
            ->limit(1);

        $qb->match('(u)-[ua:ANSWERS]->(answer:Answer)-[:IS_ANSWER_OF]->(question:Question)')
            ->where("EXISTS(answer.text_$locale)")
            ->with('u', 'u2', 'question', 'answer', 'ua');

        $qb->match('(u2)<-[:PROFILE_OF]-(:Profile)<-[:OPTION_OF]-(:Mode)<-[:INCLUDED_IN]-(:QuestionCategory)-[:CATEGORY_OF]->(question)');

        if ($showOnlyCommon) {
            $qb->match('(u2)-[ua2:ANSWERS]-(answer2:Answer)-[:IS_ANSWER_OF]-(question)');
        }

        $qb->returns('count(distinct question) as total');

        $qb->setParameters($params);

        try {
            $result = $qb->getQuery()->getResultSet();

            foreach ($result as $row) {
                $count = $row['total'];
            }

        } catch (\Exception $e) {
            throw $e;
        }

        return $count;
    }
}