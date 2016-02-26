<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Model\EnterpriseUser;

use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;
use Manager\UserManager;

class CommunityModel
{

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var
     */
    protected $um;

    /**
     * @param GraphManager $gm
     * @param UserManager $um
     */
    public function __construct(GraphManager $gm, UserManager $um)
    {

        $this->gm = $gm;
        $this->um = $um;
    }

    public function getByGroup($id)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u2:User)-[:BELONGS_TO]->(g:Group)<-[:BELONGS_TO]-(u1:User)')
            ->where('id(g) = { id }')
            ->optionalMatch('(u1)-[matches:MATCHES]->(u2)')
            ->optionalMatch('(u1)-[similarity:SIMILARITY]->(u2)')
            ->setParameters(array(
                'id' => (int)$id,
            ))
            ->returns('u1.qnoow_id as id1, u1.username as username, u1.picture as picture, COLLECT([u2.qnoow_id, matches.matching_questions, similarity.similarity]) AS relations');

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
        $picture = $row->offsetGet('picture');
        $relations = $row->offsetGet('relations');

        $relationsResult = array();

        foreach($relations as $relation) {
            $relationsResult[] = array(
                'id' => $relation[0],
                'matching' => $relation[1] ? round($relation[1] * 100) : 0,
                'similarity' => $relation[2] ? round($relation[2] * 100) : 0,
            );
        }
        return array(
            'id' => $id1,
            'username' => $username,
            'picture' => $picture,
            'relations' => $relationsResult
        );
    }
}
