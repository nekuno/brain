<?php

namespace Model\User\Affinity;

use Model\Neo4j\GraphManager;
use Everyman\Neo4j\Query\Row;

class AffinityModel
{
    const numberOfSecondsToCache = 86400;

    /**
    * @var GraphManager
    */
    protected $gm;

    public function __construct(GraphManager $gm)
    {
        $this->gm = $gm;
    }

    public function getAffinity($userId, $linkId)
    {
        $affinity = $this->getCurrentAffinity($userId, $linkId);

        $minTimestampForCache  = time() - self::numberOfSecondsToCache;
        $hasToRecalculate = ($affinity['updated'] / 1000) < $minTimestampForCache;

        if ($hasToRecalculate) {
            $this->calculateAffinity($userId, $linkId);

            $affinity = $this->getCurrentAffinity($userId, $linkId);
        }

        return $affinity;
    }

    private function getCurrentAffinity($userId, $linkId)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb
            ->match('(user:User)-[a:AFFINITY]->(link:Link)')
            ->where('user.qnoow_id = { userId } AND id(link) = { linkId }')
            ->with(
                'a.affinity AS affinity',
                'a.updated AS updated'
            )
            ->returns('affinity, updated')
        ;

        $qb->setParameters(
            array(
                'userId' => (integer)$userId,
                'linkId' => (integer)$linkId,
            )
        );

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $affinity = array(
            'affinity' => 0,
            'updated' => 0,
        );
        if ($result->count() > 0) {
            /* @var $row Row */
            $row = $result->current();
            $affinity['affinity']  = $row->offsetGet('affinity');
            $affinity['updated']  = $row->offsetGet('updated');
        }

        return $affinity;
    }

    private function calculateAffinity($userId, $linkId)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb
            ->match('(link:Link), (user:User), (u:User)-[r:LIKES]-(link)')
            ->where(
                'id(link) = { linkId }',
                'user.qnoow_id = { userId }',
                'NOT (user)-[:LIKES|:DISLIKES]-(link)'
            )
            ->with('link, user, COUNT(r) AS n')
            ->where('n >= 2')
            ->match('(link)-[:LIKES]-(similarUsers:User)-[s:SIMILARITY]-(user)')
            ->where('similarUsers <> user AND s.similarity > 0')
            ->with('user, link, similarUsers, s.similarity AS similarity')
            ->orderBy('similarity DESC')
            ->limit('4')
            ->with('user, link, similarUsers, 1-similarity AS inverseSimilarity')
            ->with(
                'user, link',
                'COUNT(similarUsers) AS numUsers',
                'REDUCE(totalInverse = 1.0, i IN COLLECT(inverseSimilarity) | totalInverse * i) AS totalInverse'
            )
            ->with('user, link, totalInverse ^ (1.0/numUsers) AS inverseGeometricMean')
            ->with('user, link, 1 - inverseGeometricMean AS affinity')
        ;

        $qb
            ->merge('(user)-[a:AFFINITY]->(link)')
            ->set(
                'a.affinity = affinity',
                'a.updated = timestamp()'
            )
            ->returns('affinity')
        ;

        $qb->setParameters(
            array(
                'userId' => (integer)$userId,
                'linkId' => (integer)$linkId,
            )
        );

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $affinity = 0;
        if ($result->count() > 0) {
            /* @var $row Row */
            $row = $result->current();
            $affinity = $row->offsetGet('affinity');
        }

        return $affinity;
    }

}