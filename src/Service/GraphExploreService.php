<?php

namespace Service;

use Everyman\Neo4j\Query\Row;
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
    public function __construct(SimilarityManager $similarityManager, MatchingManager $matchingManager)
    {
        $this->similarityManager = $similarityManager;
        $this->matchingManager = $matchingManager;
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

        $user1 = $similarityData['user1'];
        $user2 = $similarityData['user2'];
        $nodes = array(
            $this->buildUser($user1),
            $this->buildUser($user2),
        );

        $links = array(
            $this->buildRelationship(0, 1)
        );

        $countNodes = count($nodes);
        $common = $similarityData['common'];
        foreach ($common as $index => $link){
            if ($index > 100) continue;
            $newLink = $this->buildLink($link);
            $newLink['group'] = 'common';
            $nodes[] = $newLink;

            $totalIndex = $countNodes + $index;
            $links[] = $this->buildRelationship(0, $totalIndex);
            $links[] = $this->buildRelationship(1, $totalIndex);
        }

        $links1 = $similarityData['links1'];
        $countNodes = count($nodes);
        foreach ($links1 as $index => $link){
            if ($index > 100) continue;
            $newLink = $this->buildLink($link);
            $newLink['group'] = 'user1';
            $nodes[] = $newLink;

            $totalIndex = $countNodes + $index;
            $links[] = $this->buildRelationship(0, $totalIndex);
        }

        $links2 = $similarityData['links2'];
        $countNodes = count($nodes);
        foreach ($links2 as $index => $link){
            if ($index > 100) continue;
            $newLink = $this->buildLink($link);
            $newLink['group'] = 'user2';
            $nodes[] = $newLink;

            $totalIndex = $countNodes + $index;
            $links[] = $this->buildRelationship(1, $totalIndex);
        }

        return array(
            'nodes' => $nodes,
            'links' => $links,
        );
    }

    protected function matchingToGraph(array $matchingData)
    {
        $user1 = $this->buildUser($matchingData['user1']);
        $user1['group'] = 'user1';
        $user2 = $this->buildUser($matchingData['user2']);
        $user2['group'] = 'user2';

        $nodes = array(
            $user1, $user2
        );

        $links = array(
//            $this->buildRelationship(0, 1)
        );

        $questions = $matchingData['questions'];
        foreach ($questions as $index => $questionData){
            if ($index > 100) continue;
            $newQuestion = $this->buildQuestion($questionData);
            $newQuestion['group'] = 'question' . $newQuestion['id'];
            $nodes[] = $newQuestion;
//            $links[] = $this->buildRelationship(0, $index + 2);
//            $links[] = $this->buildRelationship(1, $index + 2);
        }

        foreach ($questions as $index => $questionData){
            $countNodes = count($nodes);

            $newQuestion = $this->buildQuestion($questionData);
            $questionIndex = $this->getIndexOf($newQuestion, $nodes);
            $questionGroupName = 'question' . $newQuestion['id'];

            $answers = $this->buildAnswers($questionData);
            foreach ($answers as $answerIndex => &$answer){
                $answer['group'] = $questionGroupName;

                $answerTotalIndex = $countNodes + $answerIndex;
                $links[] = $this->buildRelationship($answerTotalIndex, $questionIndex);
            }

            $nodes = array_merge($nodes, $answers);

            $user1Answered = $questionData['links']['user1Answered'];
            $user2Answered = $questionData['links']['user2Answered'];

            foreach ($user1Answered as $u1A)
            {
                $answerIndex = $this->getIndexOf($u1A, $nodes);
                $links[] = $this->buildRelationship(0, $answerIndex);
            }

            foreach ($user2Answered as $u2A)
            {
                $answerIndex = $this->getIndexOf($u2A, $nodes);
                $links[] = $this->buildRelationship(1, $answerIndex);
            }
        }

        return array(
            'nodes' => $nodes,
            'links' => $links,
        );
    }

    protected function getIndexOf($targetNode, $nodes)
    {
        foreach ($nodes as $index => $node){
            if ($targetNode['id'] === $node['id']){
                return $index;
            }
        }

        return false;
    }

    protected function buildRelationship($source, $target)
    {
        return array(
            'source' => $source,
            'target' => $target,
        );
    }

    protected function buildLink(Row $object)
    {
        return array(
            'id' => $object->offsetGet('id'),
            'url' => $object->offsetGet('url'),
            'label' => 'Link',
        );
    }

    protected function buildUser(Row $object)
    {
        return array(
            'id' => $object->offsetGet('id'),
            'username' => $object->offsetGet('username'),
            'label' => 'User',
        );
    }

    protected function buildQuestion(array $question)
    {
        return array(
            'id' => $question['id'],
            'text' => $question['text'],
            'label' => 'Question'
        );
    }

    protected function buildAnswers(array $question)
    {
        $answers = $question['answers'];

        foreach ($answers as &$answer)
        {
            $answer['label'] = 'Answer';
        }

        return $answers;
    }

}