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

    /**
     * CAUTION: Adding to this list is irreversible. Deleting from it disables use of popularity but does not delete it.
     * List of node labels that use popularity, and relationships that are counted as a measure of it.
     * All relationships are from :User node to the 'label' node.
     * @return array
     */
    public static function getPopularOptions()
    {
        return array(
            array('label' => 'Link', 'type' => 'LIKES'),
            array('label' => 'Skill', 'type' => 'HAS_SKILL'),
            array('label' => 'Language', 'type' => 'SPEAKS_LANGUAGE'),
        );
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
        $max_new_popularity = new Popularity();

        foreach (self::getPopularOptions() as $option) {
            $qb = $this->gm->createQueryBuilder();

            $qb->match('(u:User {qnoow_id: { id } })')
                ->setParameter('id', (integer)$userId);

            ///migrate old popularities to new format, or create new if link is new///
            $qb->optionalMatch('(u)-[:' . $option['type'] . ']-(old_link1:' . $option['label'] . ')')
                ->where('NOT (old_link1)-[:HAS_POPULARITY]-()')
                ->with('u', 'old_link1')
                ->with('u', 'collect(old_link1) as old_links1')
                ->optionalMatch('(u)-[:' . $option['type'] . ']-(old_link2:' . $option['label'] . ')-[:HAS_POPULARITY]-(old_pop:Popularity)')
                ->where('NOT(EXISTS(old_pop.popularity))')
                ->with('u', 'old_links1 + collect(old_link2) AS old_links')
                // Simply merge and set/remove causes error when there are no old links
                ->add('FOREACH', '(old_link in old_links | MERGE (old_link)-[:HAS_POPULARITY]->(new_pop:Popularity)
                                                      SET new_pop.popularity = CASE WHEN EXISTS(old_link.popularity) THEN old_link.popularity ELSE 0 END
                                                      SET new_pop.unpopularity = CASE WHEN EXISTS(old_link.unpopularity) THEN old_link.unpopularity ELSE 1 END
                                                      SET new_pop.timestamp = CASE WHEN EXISTS(old_link.popularity_timestamp) THEN old_link.popularity_timestamp ELSE 0 END
                                                      REMOVE old_link.popularity
                                                      REMOVE old_link.unpopularity
                                                      REMOVE old_link.popularity_timestamp
                                                         )')
                ->with('u');

            $qb->match('(u)-[:' . $option['type'] . ']->(link:' . $option['label'] . ')-[:HAS_POPULARITY]->(popularity:Popularity)')
                ->where('coalesce(popularity.timestamp, 0) < timestamp() - 0');
            $qb->with('link', 'popularity');
            $qb->optionalMatch('(link)-[r:' . $option['type'] . ']-(:User)')
                ->with('link', 'popularity', 'count(DISTINCT r) AS total')
//                ->where('total > 1')
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

            $popularities = $this->build($result);
//If user had no links to set popularity, all done
            if (empty($popularities)) {
                continue;
            }

            if ($popularities[0]->getPopularity() > $max_new_popularity->getPopularity()) {
                $max_new_popularity = $popularities[0];
            }
        }

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
        foreach (self::getPopularOptions() as $option) {
            $qb = $this->gm->createQueryBuilder();

            $qb->match('(link:' . $option['label'] . ')')
                ->where('link.popularity = 1')
                ->with('link')
                ->limit(1)
                ->optionalMatch('(link)<-[likes:' . $option['type'] . ']-()')
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
        }

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(popularity:Popularity)')
            ->where('popularity.popularity = 1')
            ->with('popularity', '0 AS amount0');
        //Add numbers to "amount" to avoid << Unexpected error during late semantic checking:   amount@185 not defined >>
        $counter = 0;
        foreach (self::getPopularOptions() as $option) {
            $qb->optionalMatch('(popularity)-[:HAS_POPULARITY]-(link:' . $option['label'] . ')-[:' . $option['type'] . ']-(likes)')
                ->with('popularity, amount'.$counter.' + count(likes) AS amount'.($counter+1));
            $counter++;
        }
        $qb->returns(' id(popularity) AS id,
                        popularity.popularity AS popularity,
                        popularity.unpopularity AS unpopularity,
                        popularity.timestamp AS timestamp,
                        amount'.$counter.' AS amount,
                        true AS new')
            ->limit(1);
        $result = $qb->getQuery()->getResultSet();
        $popularities = $this->build($result);

        if (!empty($popularities)) {
            return $popularities[0];
        }

        return null;
    }

    private function getMaxPopularityByUser($userId)
    {
        $topPopularity = new Popularity();

        foreach (self::getPopularOptions() as $option) {
            $qb = $this->gm->createQueryBuilder();

            $qb->match('(u:User {qnoow_id: { id } })')
                ->setParameter('id', (integer)$userId);

            ///migrate old popularities to new format, or create new if link is new///
            $qb->optionalMatch('(u)-[:' . $option['type'] . ']-(old_link1:' . $option['label'] . ')')
                ->where('NOT (old_link1)-[:HAS_POPULARITY]-()')
                ->with('u', 'old_link1')
                ->with('u', 'collect(old_link1) as old_links1')
                ->optionalMatch('(u)-[:' . $option['type'] . ']-(old_link2:' . $option['label'] . ')-[:HAS_POPULARITY]-(old_pop:Popularity)')
                ->where('NOT(EXISTS(old_pop.popularity))')
                ->with('u', 'old_links1 + collect(old_link2) AS old_links')
                // Simply merge and set/remove causes error when there are no old links
                ->add('FOREACH', '(old_link in old_links | MERGE (old_link)-[:HAS_POPULARITY]->(new_pop:Popularity)
                                                      SET new_pop.popularity = CASE WHEN EXISTS(old_link.popularity) THEN old_link.popularity ELSE 0 END
                                                      SET new_pop.unpopularity = CASE WHEN EXISTS(old_link.unpopularity) THEN old_link.unpopularity ELSE 1 END
                                                      SET new_pop.timestamp = CASE WHEN EXISTS(old_link.popularity_timestamp) THEN old_link.popularity_timestamp ELSE 0 END
                                                      REMOVE old_link.popularity
                                                      REMOVE old_link.unpopularity
                                                      REMOVE old_link.popularity_timestamp
                                                         )')
                ->with('u');

            $qb->match('(u)-[:' . $option['type'] . ']->(link:' . $option['label'] . ')-[:HAS_POPULARITY]->(popularity:Popularity)')
                ->where('EXISTS(popularity.popularity)');
            $qb->with('link', 'popularity');
            $qb->optionalMatch('(link)-[likes:' . $option['type'] . ']-(:User)')
                ->with('popularity, count(likes) AS amount');

            $qb->returns(' id(popularity) AS id,
                        popularity.popularity AS popularity,
                        popularity.unpopularity AS unpopularity,
                        popularity.timestamp AS timestamp,
                        amount,
                        true AS new')
                ->orderBy('popularity DESC')
                ->limit(1);

            $query = $qb->getQuery();
            $result = $query->getResultSet();
            $popularities = $this->build($result);

            if (empty($popularities)) {
                continue;
            }

            if ($popularities[0]->getPopularity() > $topPopularity->getPopularity()) {
                $topPopularity = $popularities[0];
            }
        }
        return $topPopularity;
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

        //Only Link since other types never had popularity attributes
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