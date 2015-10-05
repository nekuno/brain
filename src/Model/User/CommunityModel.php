<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Model\User;

use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;
use Model\UserModel;

class CommunityModel
{

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var UserModel
     */
    protected $um;

    /**
     * @param GraphManager $gm
     * @param UserModel $um
     */
    public function __construct(GraphManager $gm, UserModel $um)
    {

        $this->gm = $gm;
        $this->um = $um;
    }

    public function getByGroup($id)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(g:Group)<-[:BELONGS_TO]-(u1:User)-[matches:MATCHES]->(u2:User)-[:BELONGS_TO]->(g)')
            ->match('(u1)-[similarity:SIMILARITY]->(u2)')
            ->where('id(g) = { id }')
            ->setParameter('id', (int)$id)
            ->returns('u1.qnoow_id as id1, u1.username as username, {id2: collect(u2.qnoow_id), matching: collect(matches.matching_questions), similarity: collect(similarity.similarity)} AS relations');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        $return = array();

        foreach ($result as $row) {
            $return[] = $this->build($row);
        }

        return $return;
    }

    protected function build(Row $row)
    {
        $id1 = $row->offsetGet('id1');
        $username = $row->offsetGet('username');
        $relations = $row->offsetGet('relations');

        $relationsResult = array();

        foreach($relations as $relation) {
            $relationsResult[] = array(
                'id' => $relation->getProperty('id2'),
                'matching' => $relation->getProperty('matching'),
                'similarity' => $relation->getProperty('similarity'),
            );
        }
        return array(
            'id' => $id1,
            'username' => $username,
            'relations' => $relationsResult
        );
    }
}
