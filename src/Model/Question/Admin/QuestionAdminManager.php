<?php

namespace Model\Question\Admin;

use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;
use Service\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class QuestionAdminManager
{
    protected $graphManager;

    protected $questionAdminBuilder;

    protected $validator;

    /**
     * QuestionAdminManager constructor.
     * @param GraphManager $graphManager
     * @param QuestionAdminBuilder $questionAdminBuilder
     * @param ValidatorInterface $validator
     */
    public function __construct(GraphManager $graphManager, QuestionAdminBuilder $questionAdminBuilder, ValidatorInterface $validator)
    {
        $this->graphManager = $graphManager;
        $this->questionAdminBuilder = $questionAdminBuilder;
        $this->validator = $validator;
    }

    public function getById($id)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(q:Question)<-[:IS_ANSWER_OF]-(a:Answer)')
            ->where('id(q) = { id }')
            ->setParameter('id', (integer)$id)
            ->with('q', 'a')
            ->orderBy('id(a)')
            ->with('q as question, COLLECT(a) AS answers')
            ->optionalMatch('(question)-[r:RATES]-(:User)')
            ->with('question', 'answers', 'count(r) AS answered')
            ->optionalMatch('(question)-[s:SKIPS]-(:User)')
            ->with('question', 'answers', 'answered', 'count(s) AS skipped')
            ->optionalMatch('(question)-[:CATEGORY_OF]-(qc:QuestionCategory)--(mode:Mode)')
            ->returns('question', 'answers', 'answered', 'skipped', 'collect(mode.id) AS categories')
            ->limit(1);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException(sprintf('Question %d for admin not found', $id));
        }

        /* @var $row Row */
        $row = $result->current();

        return $this->questionAdminBuilder->build($row);
    }

    /**
     * @param array $data
     * @return QuestionAdmin
     */
    public function create(array $data)
    {
        $this->validateOnCreate($data);

        $answersData = $data['answerTexts'];
        $questionTexts = $data['questionTexts'];
        $qb = $this->graphManager->createQueryBuilder();

        $qb->create('(q:Question)');
        foreach ($questionTexts as $locale => $text) {
            $qb->set("q.text_$locale = {question$locale}")
                ->setParameter("question$locale", $text);
        }
        $qb->set('q.timestamp = timestamp()', 'q.ranking = 0');

        foreach ($answersData as $answerIndex => $answerData) {
            $qb->create("(a$answerIndex:Answer)-[:IS_ANSWER_OF]->(q)");
            foreach ($answerData['locales'] as $locale => $text) {
                $qb->set("a$answerIndex.text_$locale = {text$answerIndex$locale}")
                    ->setParameter("text$answerIndex$locale", $text);
            }
        };
        $qb->returns('q AS question');

        $query = $qb->getQuery();

        $result = $query->getResultSet();
        /* @var $row Row */
        $row = $result->current();

        return $this->questionAdminBuilder->build($row);
    }

    /**
     * @param array $data
     * @return QuestionAdmin
     */
    public function update(array $data)
    {
        $this->validateOnUpdate($data);

        $answerTexts = $data['answerTexts'];
        $questionTexts = $data['questionTexts'];
        $questionId = $data['questionId'];
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(q:Question)')
            ->where('id(q) = {questionId}')
            ->setParameter('questionId', (integer)$questionId);

        foreach ($questionTexts as $locale => $text) {
            $qb->set("q.text_$locale = {question$locale}")
                ->setParameter("question$locale", $text);
        }
        $qb->set('q.timestamp = timestamp()', 'q.ranking = 0');
        $qb->with('q');
        foreach ($answerTexts as $answerIndex => $answerData) {
            if (isset($answerData['answerId'])){
                $qb->match("(a$answerIndex:Answer)-[:IS_ANSWER_OF]->(q)")
                    ->where("id(a$answerIndex) = {answerId$answerIndex}")
                    ->setParameter("answerId$answerIndex", (integer)$answerData['answerId']);
            } else {
                $qb->create("(a$answerIndex:Answer)-[:IS_ANSWER_OF]->(q)");
            }

            foreach ($answerData['locales'] as $locale => $text) {
                $qb->set("a$answerIndex.text_$locale = {text$answerIndex$locale}")
                    ->setParameter("text$answerIndex$locale", $text);
            }
            $qb->with('q');
        };

        $qb->returns('q AS question');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        return $this->questionAdminBuilder->build($row);
    }

    protected function validateOnCreate(array $data)
    {
        $this->validator->validateOnCreate($data);
    }

    protected function validateOnUpdate(array $data)
    {
        $this->validator->validateOnUpdate($data);
    }
}