<?php

namespace Service\GraphExplore;

use Model\Matching\MatchingManager;
use Model\Similarity\Similarity;
use Model\Similarity\SimilarityManager;

class GraphExploreService
{
    protected $similarityManager;

    protected $matchingManager;

    /**
     * GraphExploreService constructor.
     * @param SimilarityManager $similarityManager
     * @param MatchingManager $matchingManager
     */
    public function __construct(SimilarityManager $similarityManager, MatchingManager $matchingManager, GraphDataBuilder $graphDataBuilder)
    {
        $this->similarityManager = $similarityManager;
        $this->matchingManager = $matchingManager;
        $this->graphDataBuilder = $graphDataBuilder;
    }

    public function getSimilarity($userId1, $userId2)
    {
        $similarityData = $this->similarityManager->getDetailedSimilarity($userId1, $userId2);

        return $this->similarityToGraph($similarityData);
    }

    public function getMatching($userId1, $userId2)
    {
        $matchingData = $this->matchingManager->getDetailedMatching($userId1, $userId2);

        return $this->matchingToGraph($matchingData);
    }

    protected function similarityToGraph(array $similarityData)
    {
        /** @var Similarity $similarity */
        $similarity = $similarityData['similarity'];

        $nodes = array();
        $links = array();

        $nodes = $this->addTwoUsers($similarityData, $nodes);

        $links[] = $this->graphDataBuilder->buildRelationship(0, 1);

        $countNodes = count($nodes);
        $common = $similarityData['common'];
        foreach ($common as $index => $link) {
            if ($index > 100) {
                continue;
            }
            $newLink = $this->graphDataBuilder->buildLink($link);
            $newLink['group'] = 'common';
            $nodes[] = $newLink;

            $totalIndex = $countNodes + $index;
            $links[] = $this->graphDataBuilder->buildRelationship(0, $totalIndex);
            $links[] = $this->graphDataBuilder->buildRelationship(1, $totalIndex);
        }

        $links1 = $similarityData['links1'];
        $countNodes = count($nodes);
        foreach ($links1 as $index => $link) {
            if ($index > 100) {
                continue;
            }
            $newLink = $this->graphDataBuilder->buildLink($link);
            $newLink['group'] = 'user1';
            $nodes[] = $newLink;

            $totalIndex = $countNodes + $index;
            $links[] = $this->graphDataBuilder->buildRelationship(0, $totalIndex);
        }

        $links2 = $similarityData['links2'];
        $countNodes = count($nodes);
        foreach ($links2 as $index => $link) {
            if ($index > 100) {
                continue;
            }
            $newLink = $this->graphDataBuilder->buildLink($link);
            $newLink['group'] = 'user2';
            $nodes[] = $newLink;

            $totalIndex = $countNodes + $index;
            $links[] = $this->graphDataBuilder->buildRelationship(1, $totalIndex);
        }

        return array(
            'nodes' => $nodes,
            'links' => $links,
        );
    }

    protected function matchingToGraph(array $matchingData)
    {
        $nodes = array();
        $links = array();

        $nodes = $this->addTwoUsers($matchingData, $nodes);

        $questions = $matchingData['questions'];
        $nodes = $this->addQuestionNodes($questions, $nodes);

        foreach ($questions as $questionData) {

            $newQuestion = $this->graphDataBuilder->buildQuestion($questionData);

            $answers = $this->graphDataBuilder->buildAnswersData($questionData);
            foreach ($answers as &$answer)
            {
                $answer['group'] = $this->buildQuestionGroupName($newQuestion);
            }

            $links = $this->addAnswerLinksToQuestion($answers, $newQuestion, $links, $nodes);

            $nodes = $this->addAnswerNodes($answers, $nodes);

            $user1Answered = $questionData['links']['user1Answered'];
            $links = $this->addAnsweredLinksToUser($user1Answered, 0, $links, $nodes);

            $user2Answered = $questionData['links']['user2Answered'];
            $links = $this->addAnsweredLinksToUser($user2Answered, 1, $links, $nodes);
        }

        return array(
            'nodes' => $nodes,
            'links' => $links,
        );
    }

    protected function getIndexOf($targetNode, $nodes)
    {
        foreach ($nodes as $index => $node) {
            if ($targetNode['id'] === $node['id']) {
                return $index;
            }
        }

        return false;
    }

    protected function addTwoUsers($data, $nodes)
    {
        $user1 = $this->graphDataBuilder->buildUser($data['user1']);
        $user1['group'] = 'user1';
        $nodes[] = $user1;

        $user2 = $this->graphDataBuilder->buildUser($data['user2']);
        $user2['group'] = 'user2';
        $nodes[] = $user2;

        return $nodes;
    }

    protected function addQuestionNodes($questions, $nodes)
    {
        foreach ($questions as $index => $questionData) {
            if ($index > 100) {
                continue;
            }
            $newQuestion = $this->graphDataBuilder->buildQuestion($questionData);
            $newQuestion['group'] = 'question' . $newQuestion['id'];
            $nodes[] = $newQuestion;
        }

        return $nodes;
    }

    protected function addAnswerNodes($answers, $nodes)
    {
        return array_merge($nodes, $answers);
    }

    protected function addAnswerLinksToQuestion($answers, $question, $links, $nodes)
    {
        $countNodes = count($nodes);
        $questionIndex = $this->getIndexOf($question, $nodes);

        foreach ($answers as $answerIndex => &$answer) {
            $answer['group'] = $this->buildQuestionGroupName($question);

            $answerTotalIndex = $countNodes + $answerIndex;
            $links[] = $this->graphDataBuilder->buildIsAnswerOf($answerTotalIndex, $questionIndex);
        }

        return $links;
    }

    protected function addAnsweredLinksToUser($answered, $userId, $links, $nodes)
    {
        foreach ($answered as $u1A) {
            $answerIndex = $this->getIndexOf($u1A, $nodes);
            $links[] = $this->graphDataBuilder->buildAnswers($userId, $answerIndex);
        }

        return $links;
    }

    protected function buildQuestionGroupName($question)
    {
        return 'question' . $question['id'];
    }
}