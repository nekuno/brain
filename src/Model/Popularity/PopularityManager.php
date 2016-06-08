<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

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

        ///migrate old popularities to new format///
        $qb->optionalMatch('(u)-[:LIKES]-(old_link:Link)')
            ->where('EXISTS(old_link.popularity)')
            ->with('u', 'collect(old_link) as old_links')
            // Simply merge and set/remove causes error when there are no old links
            ->add('FOREACH', '(old_link in old_links | MERGE (old_link)-[:HAS_POPULARITY]->(new_pop:Popularity)
                                                      SET new_pop.popularity = old_link.popularity
                                                      SET new_pop.unpopularity = old_link.unpopularity
                                                      SET new_pop.timestamp = old_link.popularity_timestamp
                                                      REMOVE old_link.popularity
                                                      REMOVE old_link.unpopularity
                                                      REMOVE old_link.popularity_timestamp
                                                         )')
            ->with('u');

        $qb->match('(u)-[:LIKES]->(link:Link)-[:HAS_POPULARITY]->(popularity:Popularity)')
//            ->where('popularity.timestamp < timestamp() - 1000*3600*24');
            ->where('popularity.timestamp < timestamp() - 0');
        $qb->with('link', 'popularity');
        $qb->optionalMatch('(link)-[r:LIKES]-(:User)')
            ->with('link', 'popularity', 'count(DISTINCT r) AS total')
            ->where('total > 1')
            ->with('popularity', 'toFloat(total) AS total')
            ->with('popularity', 'CASE
                                    WHEN total < {max} THEN total
                                    ELSE {max}
                                END as total');
        $qb->setParameter('max', floatval($max_popularity->getAmount()));

        $qb->set(
            'popularity.popularity = (total/{max})^3',
            'popularity.unpopularity = (1-(total/{max}))^3',
            'popularity.timestamp = timestamp()'
        );

        $qb->returns('   id(popularity) AS id',
            'popularity.popularity AS popularity',
            'popularity.unpopularity AS unpopularity',
            'popularity.timestamp AS timestamp',
            'total AS amount',
            'true AS new')
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

        return true;
    }

    /**
     * Looks for :Link(popularity) and :Popularity(popularity) for now
     */
    private function getMaxPopularity()
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(link:Link)')
            ->where('link.popularity = 1')
            ->with('link')
            ->limit(1)
            ->optionalMatch('(link)<-[likes:LIKES]-()')
            ->returns('  id(link) AS id',
                'link.popularity AS popularity',
                'link.unpopularity AS unpopularity',
                'link.popularity_timestamp AS timestamp',
                'count(likes) AS amount',
                'false AS new');
        $result = $qb->getQuery()->getResultSet();
        $popularities = $this->build($result);

        if (!empty($popularities)) {
            return $popularities[0];
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(popularity:Popularity)')
            ->where('popularity.popularity = 1')
            ->with('popularity')
            ->optionalMatch('(popularity)-[:HAS_POPULARITY]-(:Link)-[:LIKES]-(likes)')
            ->returns(' id(popularity) AS id,
                        popularity.popularity AS popularity,
                        popularity.unpopularity AS unpopularity,
                        popularity.timestamp AS timestamp,
                        count(likes) AS amount,
                        true AS new')
            ->limit(1);
        $result = $qb->getQuery()->getResultSet();
        $popularities = $this->build($result);

        if (!empty($popularities)) {
            return $popularities[0];
        }

        return null;
    }

    private function getMaxPopularityByUser($userId){
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(u:User {qnoow_id: { id } })')
            ->setParameter('id', (integer)$userId);

        ///migrate old popularities to new format///
        $qb->optionalMatch('(u)-[:LIKES]-(old_link:Link)')
            ->where('EXISTS(old_link.popularity)')
            ->with('u', 'collect(old_link) as old_links')
            // Simply merge and set/remove causes error when there are no old links
            ->add('FOREACH', '(old_link in old_links | MERGE (old_link)-[:HAS_POPULARITY]->(new_pop:Popularity)
                                                      SET new_pop.popularity = old_link.popularity
                                                      SET new_pop.unpopularity = old_link.unpopularity
                                                      SET new_pop.timestamp = old_link.popularity_timestamp
                                                      REMOVE old_link.popularity
                                                      REMOVE old_link.unpopularity
                                                      REMOVE old_link.popularity_timestamp
                                                         )')
            ->with('u');

        $qb->match('(u)-[:LIKES]->(link:Link)-[:HAS_POPULARITY]->(popularity:Popularity)');
        $qb->with('link', 'popularity');
        $qb->optionalMatch('(link)-[likes:LIKES]-(:User)')
            ->returns(' id(popularity) AS id,
                        popularity.popularity AS popularity,
                        popularity.unpopularity AS unpopularity,
                        popularity.timestamp AS timestamp,
                        count(likes) AS amount,
                        true AS new')
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
            ->optionalMatch('(popularity)-[:HAS_POPULARITY]-(:Link)-[likes:LIKES]-()')
            ->with('popularity', 'count(likes) AS amount')
            ->orderBy('popularity.popularity DESC')
            //Can´t reliably keep order of collect(popularity) and collect(amount) http://stackoverflow.com/questions/28099125/how-to-unwind-multiple-collections
            ->with('collect(popularity) AS popularities, max(amount) as max')
            ->unwind('popularities AS popularity')
            ->with('popularity', 'max')
            ->optionalMatch('(popularity)-[:HAS_POPULARITY]-(:Link)-[likes:LIKES]-()')
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
            ->returns(' id(popularity) AS id,
                        popularity.popularity AS popularity,
                        popularity.unpopularity AS unpopularity,
                        popularity.timestamp AS timestamp,
                        amount,
                        true AS new');
        $query = $qb->getQuery();
        $result = $query->getResultSet();

        return $this->build($result);
    }

    private function migrateMaxPopularity()
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(l:Link)')
            ->where('l.popularity = 1')
            ->with('l')
            ->merge('(l)-[:HAS_POPULARITY]->(new_pop:Popularity)')
            ->set('new_pop.popularity = l.popularity',
                'new_pop.unpopularity = l.unpopularity',
                'new_pop.timestamp = l.popularity_timestamp')
            ->with('l')
            ->remove('l.popularity', 'l.unpopularity', 'l.popularity_timestamp');
        $qb->getQuery()->getResultSet();
    }

    /**
     * @param ResultSet $result
     * @return Popularity[]
     */
    private function build(ResultSet $result)
    {
        $popularities = array();
        /** @var Row $row */
        foreach ($result as $row) {

            $popularity = new Popularity();

            $popularity->setId($row->offsetGet('id'));
            $popularity->setPopularity($row->offsetGet('popularity'));
            $popularity->setUnpopularity($row->offsetGet('unpopularity'));
            $popularity->setTimestamp($row->offsetGet('timestamp'));
            $popularity->setAmount($row->offsetGet('amount'));
            $popularity->setNew($row->offsetGet('new'));

            $popularities[] = $popularity;
        }

        return $popularities;
    }
}