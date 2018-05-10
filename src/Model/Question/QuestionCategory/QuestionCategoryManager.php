<?php

namespace Model\Question\QuestionCategory;

use Everyman\Neo4j\Query\ResultSet;
use Model\Neo4j\GraphManager;

class QuestionCategoryManager
{
    protected $graphManager;

    /**
     * @param $graphManager
     */
    public function __construct(GraphManager $graphManager)
    {
        $this->graphManager = $graphManager;
    }

    public function getQuestionCategories($questionId)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(q:Question)')
            ->where('id(q) = {questionId}')
            ->setParameter('questionId', (integer)$questionId);

        $qb->optionalMatch('(q)<-[:CATEGORY_OF]-(category:QuestionCategory)-[:INCLUDED_IN]-(mode:Mode)');

        $qb->returns('{id: id(category), name: mode.id} AS category');

        $result = $qb->getQuery()->getResultSet();

        return $this->buildMany($result);
    }

    public function setQuestionCategories($questionId, array $data)
    {
        $categories = $data['categories'];
        
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(q:Question)')
            ->where('id(q) = {questionId}')
            ->setParameter('questionId', (integer)$questionId);

        $qb->optionalMatch('(q)<-[r:CATEGORY_OF]-(:QuestionCategory)')
            ->delete('r')
            ->with('q');

        $qb->match('(category:QuestionCategory)--(mode:Mode)')
            ->where('mode.id IN {categories}')
            ->setParameter('categories', $categories);
        $qb->merge('(category)-[r:CATEGORY_OF]->(q)');

        $qb->returns('{id: id(category), name: mode.id} AS category');

        $result = $qb->getQuery()->getResultSet();

        return $this->buildMany($result);
    }

    protected function buildMany(ResultSet $resultSet)
    {
        $categories = array();

        foreach ($resultSet as $row)
        {
            $categoryResult = $row->offsetGet('category');

            $category = new QuestionCategory();
            $category->setId($categoryResult->offsetGet('id'));
            $category->setName($categoryResult->offsetGet('name'));

            $categories[] = $category;
        }

        return $categories;
    }

    public function createQuestionCategoriesFromModes()
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(mode:Mode)')
            ->merge('(mode)<-[:INCLUDED_IN]-(:QuestionCategory)');

        $qb->getQuery()->getResultSet();
    }
}