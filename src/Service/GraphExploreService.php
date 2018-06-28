<?php

namespace Service;

use Everyman\Neo4j\Query\Row;
use Model\Similarity\Similarity;
use Model\Similarity\SimilarityManager;

class GraphExploreService
{
    protected $similarityManager;

    /**
     * GraphExploreService constructor.
     * @param $similarityManager
     */
    public function __construct(SimilarityManager $similarityManager)
    {
        $this->similarityManager = $similarityManager;
    }

    public function getSimilarity($userId1, $userId2)
    {
        $similarityData = $this->similarityManager->getDetailedSimilarity($userId1, $userId2);

        return $this->similarityToGraph($similarityData);
    }

    protected function similarityToGraph(array $similarityData)
    {
        $user1 = $similarityData['user1'];
        $user2 = $similarityData['user2'];
        /** @var Similarity $similarity */
        $similarity = $similarityData['similarity'];
        $common = $similarityData['common'];
        $links1 = $similarityData['links1'];
        $links2 = $similarityData['links2'];

        $nodes = array(
            $this->buildUser($user1),
            $this->buildUser($user2),
        );

        $links = array(
            $this->buildRelationship(0, 1)
        );

        $countNodes = count($nodes);
        foreach ($common as $index => $link){
            if ($index > 100) continue;
            $newLink = $this->buildLink($link);
            $newLink['group'] = 'common';
            $nodes[] = $newLink;

            $totalIndex = $countNodes + $index;
            $links[] = $this->buildRelationship(0, $totalIndex);
            $links[] = $this->buildRelationship(1, $totalIndex);
        }

        $countNodes = count($nodes);
        foreach ($links1 as $index => $link){
            if ($index > 100) continue;
            $newLink = $this->buildLink($link);
            $newLink['group'] = 'user1';
            $nodes[] = $newLink;

            $totalIndex = $countNodes + $index;
            $links[] = $this->buildRelationship(0, $totalIndex);
        }

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
            'group' => 'user',
        );
    }

}