<?php

namespace Model\EnterpriseUser;

use Everyman\Neo4j\Query\Row;
use Model\Photo\PhotoManager;
use Model\Neo4j\GraphManager;
use Model\User\UserManager;

class CommunityManager
{

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var UserManager
     */
    protected $um;

    /**
     * @var PhotoManager
     */
    protected $pm;

    /**
     * @param GraphManager $gm
     * @param UserManager $um
     * @param PhotoManager $pm
     */
    public function __construct(GraphManager $gm, UserManager $um, PhotoManager $pm)
    {

        $this->gm = $gm;
        $this->um = $um;
        $this->pm = $pm;
    }

    public function getByGroup($id)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u2:User)-[:BELONGS_TO]->(g:Group)<-[:BELONGS_TO]-(u1:User)')
            ->where('id(g) = { id }')
            ->optionalMatch('(u1)-[matches:MATCHES]-(u2)')
            ->optionalMatch('(u1)-[similarity:SIMILARITY]-(u2)')
            ->setParameters(
                array(
                    'id' => (int)$id,
                )
            )
            ->returns('u1.qnoow_id as id1, u1.username as username, u1.photo as photo, COLLECT([u2.qnoow_id, matches.matching_questions, similarity.similarity]) AS relations');

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

        $photo = $this->pm->createProfilePhoto();
        $photo->setPath($row->offsetGet('photo'));
        $photo->setUserId($id1);

        $relationsResult = array();

        foreach ($relations as $relation) {
            $relationsResult[] = array(
                'id' => $relation[0],
                'matching' => $relation[1] ? round($relation[1] * 100) : 0,
                'similarity' => $relation[2] ? round($relation[2] * 100) : 0,
            );
        }

        return array(
            'id' => $id1,
            'username' => $username,
            'photo' => $photo,
            'relations' => $relationsResult
        );
    }
}
