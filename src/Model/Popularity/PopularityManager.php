<?php

namespace Model\Popularity;

use Everyman\Neo4j\Query\ResultSet;
use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;

class PopularityManager
{
    protected $gm;

    /**
     * PopularityManager constructor.
     * @param $gm
     */
    public function __construct(GraphManager $gm)
    {
        $this->gm = $gm;
    }

    public function updatePopularity($linkId)
    {
        $max_popularity = $this->getMaxPopularity();

        $maxAmount = $max_popularity ? floatval($max_popularity->getAmount()) : 1.0;

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(l:Link)')
            ->where('id(l) = {nodeId}')
            ->setParameter('nodeId', (integer)$linkId);

        $qb->merge('(l)-[:HAS_POPULARITY]->(popularity:Popularity)')
            ->with('l', 'popularity')
            ->optionalMatch('(l)<-[likes:LIKES]-(:User)')
            ->with('popularity', 'toFloat(count(likes)) AS total')
            ->setParameter('max', $maxAmount)
            ->with('popularity', 'total', '(total/{max})^3 AS popularityValue', '(1.0-(total/{max}))^3 AS unpopularityValue')
            ->with('popularity', 'total', 'CASE WHEN popularityValue > 1 THEN 1 ELSE popularityValue END AS popularityValue', 'CASE WHEN unpopularityValue < 0 THEN 0 ELSE unpopularityValue END AS unpopularityValue');

        $qb->set(
            'popularity.popularity = popularityValue',
            'popularity.unpopularity = unpopularityValue',
            'popularity.timestamp = timestamp()'
        );
        $qb->returns(
            'id(popularity) AS id',
            'popularity.popularity AS popularity',
            'popularity.unpopularity AS unpopularity',
            'popularity.timestamp AS timestamp',
            'total AS amount',
            'true AS new'
        )
            ->orderBy('popularity DESC');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        if ($result->count() == 0) {
            return false;
        }

        $popularity = $this->buildOne($result->current());
        $this->checkIfDelete($popularity);

        $isNewLink = $maxAmount == 1;
        if ($isNewLink || $popularity->getPopularity() == 1){
            $this->updateMaxPopularity();
        }

        return $popularity;
    }

    public function deleteOneByUrl($url)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(l:Link)')
            ->where('l.url = {url}')
            ->setParameter('url', $url);

        $qb->match('(l)-[rel:HAS_POPULARITY]->(popularity:Popularity)')
            ->delete('rel', 'popularity');

        $qb->getQuery()->getResultSet();
    }

    public function updatePopularityByUser($userId)
    {
        $max_popularity = $this->getMaxPopularity();

        if (null === $max_popularity) {
            $max_popularity = $this->getMaxPopularityByUser($userId);
            if (null === $max_popularity) {
                return true;
            }
        }

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(u:User {qnoow_id: { id } })')
            ->setParameter('id', (integer)$userId);

        $qb->match('(u)-[:LIKES]->(link:Link)')
            ->merge('(link)-[:HAS_POPULARITY]->(popularity:Popularity)');
        $qb->with('link', 'popularity')
            ->where('coalesce(popularity.timestamp, 0) < timestamp() - 1000*3600*24');
        $qb->optionalMatch('(link)-[r:LIKES]-(:User)')
            ->with('link', 'popularity', 'count(DISTINCT r) AS total')
            ->with('popularity', 'toFloat(total) AS total')
            ->with(
                'popularity',
                'CASE
                                    WHEN total < {max} THEN total
                                    ELSE {max}
                                END as total'
            );
        $qb->setParameter('max', floatval($max_popularity->getAmount()));

        $qb->set(
            'popularity.popularity = (total/{max})^3',
            'popularity.unpopularity = (1-(total/{max}))^3',
            'popularity.timestamp = timestamp()'
        );

        $qb->returns(
            'id(popularity) AS id',
            'popularity.popularity AS popularity',
            'popularity.unpopularity AS unpopularity',
            'popularity.timestamp AS timestamp',
            'total AS amount',
            'true AS new'
        )
            ->orderBy('popularity DESC');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        //If user had no links to set popularity, all done
        if ($result->count() == 0) {
            return true;
        }
        $popularities = $this->build($result);

        $max_new_popularity = $popularities[0];
        if ($max_new_popularity->getPopularity() == 1) {
            if (!$max_new_popularity->isNew()) {
                $this->migrateMaxPopularity();
            }
            $this->updateMaxPopularity();
        }

        foreach ($popularities as $popularity) {
            $this->checkIfDelete($popularity);
        }

        return true;
    }

    protected function checkIfDelete(Popularity $popularity)
    {
        if ($popularity->getAmount() <= 0) {
            $popularityId = $popularity->getId();
            $this->deleteOneById($popularityId);
        }
    }

    public function deleteOneById($popularityId)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(p:Popularity)')
            ->where('id(p) = {nodeId}')
            ->setParameter('nodeId', (integer)$popularityId)
            ->with('p')
            ->limit(1);

        $qb->detachDelete('p');

        $qb->getQuery()->getResultSet();
    }

    public function deleteOneByLinkId($linkId)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(l:Link)')
            ->where('id(l) = {nodeId}')
            ->setParameter('nodeId', (integer)$linkId);

        $qb->match('(l)-[rel:HAS_POPULARITY]->(popularity:Popularity)')
            ->delete('rel', 'popularity');

        $qb->getQuery()->getResultSet();
    }

    public function getPopularitiesByUser($userId, $countGhost = true)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(u:User {qnoow_id: { id } })-[:LIKES]->(link:Link)<-[likes:LIKES]-(u2:User)')
            ->setParameter('id', (integer)$userId);
        if (!$countGhost) {
            $qb->where('NOT (u2:GhostUser)');
        }
        $qb->with('link, count(likes) AS amount');

        $qb->match('(link)-[:HAS_POPULARITY]->(popularity:Popularity)');
        $qb->returns(
            '   id(popularity) AS id',
            'popularity.popularity AS popularity',
            'popularity.unpopularity AS unpopularity',
            'popularity.timestamp AS timestamp',
            'amount',
            'true AS new'
        );

        $result = $qb->getQuery()->getResultSet();

        return $this->build($result);
    }

    /**
     * @param ResultSet $result
     * @return Popularity[]
     */
    public function build(ResultSet $result)
    {
        $popularities = array();
        /** @var Row $row */
        foreach ($result as $row) {

            $popularities[] = $this->buildOne($row);
        }

        return $popularities;
    }

    private function buildOne(Row $row)
    {
        $popularity = new Popularity();

        $popularity->setId($row->offsetGet('id'));
        $popularity->setPopularity($row->offsetGet('popularity'));
        $popularity->setUnpopularity($row->offsetGet('unpopularity'));
        $popularity->setTimestamp($row->offsetGet('timestamp'));
        $popularity->setAmount($row->offsetGet('amount'));
        $popularity->setNew($row->offsetGet('new'));

        return $popularity;
    }

    /**
     * Looks for :Link(popularity) and :Popularity(popularity) for now
     */
    public function getMaxPopularity()
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(link:Link)')
            ->where('link.popularity = 1')
            ->with('link')
            ->limit(1)
            ->optionalMatch('(link)<-[likes:LIKES]-()')
            ->returns(
                '  id(link) AS id',
                'link.popularity AS popularity',
                'link.unpopularity AS unpopularity',
                'link.popularity_timestamp AS timestamp',
                'count(likes) AS amount',
                'false AS new'
            );
        $result = $qb->getQuery()->getResultSet();
        $popularities = $this->build($result);

        if (!empty($popularities)) {
            return $popularities[0];
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(popularity:Popularity)')
            ->where('popularity.popularity = 1')
            ->with('popularity')
            ->optionalMatch('(popularity)<-[:HAS_POPULARITY]-(:Link)<-[:LIKES]-(likes)')
            ->returns(
                ' id(popularity) AS id,
                        popularity.popularity AS popularity,
                        popularity.unpopularity AS unpopularity,
                        popularity.timestamp AS timestamp,
                        count(likes) AS amount,
                        true AS new'
            )
            ->limit(1);
        $result = $qb->getQuery()->getResultSet();
        $popularities = $this->build($result);

        if (!empty($popularities)) {
            return $popularities[0];
        }

//        $maxPopularities = $this->calculateNewMaxPopularity();
//        if (!empty($maxPopularities)) {
//            return $maxPopularities[0];
//        }

        return null;
    }

    private function getMaxPopularityByUser($userId)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(u:User {qnoow_id: { id } })')
            ->setParameter('id', (integer)$userId);

        ///migrate old popularities to new format, or create new if link is new///
        $qb->optionalMatch('(u)-[:LIKES]-(old_link:Link)')
            ->where('NOT (old_link)-[:HAS_POPULARITY]->()')
            ->with('u', 'old_link')
            ->with('u', 'collect(old_link) as old_links')
            // Simply merge and set/remove causes error when there are no old links
            ->for_each(
                '(old_link in old_links | MERGE (old_link)-[:HAS_POPULARITY]->(new_pop:Popularity)
                                                      SET new_pop.popularity = CASE WHEN EXISTS(old_link.popularity) THEN old_link.popularity ELSE 0 END
                                                      SET new_pop.unpopularity = CASE WHEN EXISTS(old_link.unpopularity) THEN old_link.unpopularity ELSE 1 END
                                                      SET new_pop.timestamp = CASE WHEN EXISTS(old_link.popularity_timestamp) THEN old_link.popularity_timestamp ELSE 0 END
                                                      REMOVE old_link.popularity
                                                      REMOVE old_link.unpopularity
                                                      REMOVE old_link.popularity_timestamp
                                                         )'
            )
            ->with('u');

        $qb->match('(u)-[:LIKES]->(link:Link)-[:HAS_POPULARITY]->(popularity:Popularity)')
            ->where('EXISTS(popularity.popularity)');
        $qb->with('link', 'popularity');
        $qb->optionalMatch('(link)<-[likes:LIKES]-(:User)')
            ->returns(
                ' id(popularity) AS id,
                        popularity.popularity AS popularity,
                        popularity.unpopularity AS unpopularity,
                        popularity.timestamp AS timestamp,
                        count(likes) AS amount,
                        true AS new'
            )
            ->orderBy('popularity DESC')
            ->limit(1);

        $result = $qb->getQuery()->getResultSet();

        $popularities = $this->build($result);

        return empty($popularities) ? null : $popularities[0];
    }

    private function updateMaxPopularity()
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(popularity:Popularity)')
            ->where('popularity.popularity = 1')
            ->with('popularity')
            ->optionalMatch('(popularity)<-[:HAS_POPULARITY]-(:Link)<-[likes:LIKES]-()')
            ->with('popularity', 'count(likes) AS amount')
            ->orderBy('popularity.popularity DESC')
            //Can´t reliably keep order of collect(popularity) and collect(amount) http://stackoverflow.com/questions/28099125/how-to-unwind-multiple-collections
            ->with('collect(popularity) AS popularities, max(amount) as max')
            ->unwind('popularities AS popularity')
            ->with('popularity', 'max')
            ->optionalMatch('(popularity)<-[:HAS_POPULARITY]-(:Link)<-[likes:LIKES]-()')
            ->with('popularity', 'max', 'count(likes) AS amount')
            ->set(
                'popularity.popularity = CASE max
                                WHEN 0 THEN 0
                                ELSE (amount/max)^3
                            END',
                'popularity.unpopularity = CASE max
                                WHEN 0 THEN 1
                                ELSE (1-(amount/max))^3
                            END',
                'popularity.timestamp = CASE max
                                            WHEN 0 THEN 0
                                            ELSE timestamp()
                                        END'
            )
            ->returns(
                ' id(popularity) AS id,
                        popularity.popularity AS popularity,
                        popularity.unpopularity AS unpopularity,
                        popularity.timestamp AS timestamp,
                        amount,
                        true AS new'
            );
        $query = $qb->getQuery();
        $result = $query->getResultSet();

        return $this->build($result);
    }

    private function calculateNewMaxPopularity()
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(popularity)')
            ->optionalMatch('(popularity)<-[:HAS_POPULARITY]-(:Link)<-[likes:LIKES]-()')
            ->with('popularity', 'count(likes) AS amount')
            ->orderBy('amount DESC')
            ->limit(1)
            ->set(
                'popularity.popularity = 1',
                'popularity.unpopularity = 0',
                'popularity.timestamp = timestamp()'
            )
            ->returns(
                ' id(popularity) AS id,
                        popularity.popularity AS popularity,
                        popularity.unpopularity AS unpopularity,
                        popularity.timestamp AS timestamp,
                        amount,
                        true AS new'
            );
        $query = $qb->getQuery();
        $result = $query->getResultSet();

        return $this->build($result);
    }

    /**
     * @param $likes
     * @param $maxAmount
     * @return Popularity
     */
    public function calculatePopularity($likes, $maxAmount = null)
    {
        if (null == $maxAmount) {
            $maxPopularity = $this->getMaxPopularity();
            $maxAmount = $maxPopularity->getAmount();
        }

        $popularity = new Popularity(true);
        $popularity->setAmount($likes);
        $popularity->setPopularity(pow(floatval($likes) / floatval($maxAmount), 3));
        $popularity->setUnpopularity(1 - pow(floatval($likes) / floatval($maxAmount), 3));

        return $popularity;

    }

    private function migrateMaxPopularity()
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(l:Link)')
            ->where('l.popularity = 1')
            ->with('l')
            ->merge('(l)-[:HAS_POPULARITY]->(new_pop:Popularity)')
            ->set(
                'new_pop.popularity = l.popularity',
                'new_pop.unpopularity = l.unpopularity',
                'new_pop.timestamp = l.popularity_timestamp'
            )
            ->with('l')
            ->remove('l.popularity', 'l.unpopularity', 'l.popularity_timestamp');
        $qb->getQuery()->getResultSet();
    }
}