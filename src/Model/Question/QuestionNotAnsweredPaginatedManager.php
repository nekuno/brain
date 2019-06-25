<?php

namespace Model\Question;

use Everyman\Neo4j\Query\ResultSet;
use Model\Neo4j\GraphManager;
use Paginator\PaginatedInterface;
use Everyman\Neo4j\Query\Row;

class QuestionNotAnsweredPaginatedManager implements PaginatedInterface
{

    protected $questionModel;
    protected $graphManager;

    /**
     * @param GraphManager $graphManager
     * @param QuestionManager $questionModel
     */
    public function __construct(GraphManager $graphManager, QuestionManager $questionModel)
    {
        $this->graphManager = $graphManager;
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
     * @return array
     * @throws \Exception
     */
    public function slice(array $filters, $offset, $limit)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $id = $filters['id'];
        $id2 = $filters['id2'];
        $locale = $filters['locale'];

        $qb->match('(otherUser:User), (ownUser:User)')
            ->where('otherUser.qnoow_id = {otherUserId} AND ownUser.qnoow_id = {ownUserId}')
            ->with('otherUser', 'ownUser')
            ->limit(1);
        $qb->match('(otherUser)-[:ANSWERS]->(answer:Answer)-[:IS_ANSWER_OF]->(question:Question)')
            ->where("NOT (ownUser)-[:ANSWERS]->(:Answer)-[:IS_ANSWER_OF]->(question) AND EXISTS(answer.text_$locale) AND EXISTS(question.text_$locale)");
        $qb->with('question')
            ->match('(possible_answers:Answer)-[:IS_ANSWER_OF]-(question)');
        $qb->returns(
            'question',
            '{
                question: question,
                answers: collect(distinct possible_answers)
            } as other_not_answered_questions'
        )
            ->orderBy('id(question)')
            ->skip('{offset}')
            ->limit('{limit}');
        $qb->setParameters(
            array(
                'otherUserId' => (integer)$id,
                'ownUserId' => (integer)$id2,
                'offset' => (integer)$offset,
                'limit' => (integer)$limit
            )
        );
        $result = $qb->getQuery()->getResultSet();

        $other_not_answered_questions_results = $this->buildNotAnsweredQuestionResults($result, 'other_not_answered_questions', $locale);

        $resultArray = array();
        $noResults = empty($other_not_answered_questions_results);
        if (!$noResults) {
            $resultArray = array(
                'otherQuestions' => [],
                'ownQuestions' => [],
                'otherNotAnsweredQuestions' => $other_not_answered_questions_results
            );
        }

        return $resultArray;
    }

    private function buildNotAnsweredQuestionResults(ResultSet $result, $questionsKey, $locale)
    {
        $questions_results = array();
        /* @var $row Row */
        foreach ($result as $row) {
            if ($row->offsetGet($questionsKey)->offsetExists('question')) {
                $questions = $row->offsetGet($questionsKey);
                $questionId = $questions->offsetGet('question')->getId();
                $questions_results['questions'][$questionId] = $this->questionModel->build($questions, $locale);

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
     * @return int
     * @throws \Exception
     */
    public function countTotal(array $filters)
    {
        $count = 0;

        $qb = $this->graphManager->createQueryBuilder();

        $id = $filters['id'];
        $id2 = $filters['id2'];
        $locale = $filters['locale'];

        $qb->match('(otherUser:User), (ownUser:User)')
            ->where('otherUser.qnoow_id = {otherUserId} AND ownUser.qnoow_id = {ownUserId}')
            ->with('otherUser', 'ownUser')
            ->limit(1);
        $qb->match('(otherUser)-[:ANSWERS]->(answer:Answer)-[:IS_ANSWER_OF]->(question:Question)')
            ->where("NOT (ownUser)-[:ANSWERS]->(:Answer)-[:IS_ANSWER_OF]->(question) AND EXISTS(answer.text_$locale) AND EXISTS(question.text_$locale)");
        $qb->returns('count(distinct question) as total');
        $qb->setParameters(
            array(
                'otherUserId' => (integer)$id,
                'ownUserId' => (integer)$id2,
            )
        );

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