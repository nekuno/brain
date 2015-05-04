<?php

namespace Model\User;

use Event\UserDataEvent;
use Everyman\Neo4j\Cypher\Query;
use Model\Neo4j\GraphManager;

/**
 * Class GroupModel
 *
 * @package Model\User
 */
class GroupModel
{

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @param GraphManager $gm
     */
    public function __construct(GraphManager $gm)
    {

        $this->gm = $gm;
    }

    /**
     * @param $groupName
     * @throws \Exception
     * @return array
     */
    public function create($groupName)
    {
        $qb = $this->gm->createQueryBuilder();

            $qb ->merge('(g:Group{groupName:{groupName}})')
                ->returns('g');

        $qb->setParameters(
            array(
                'groupName'=>$groupName
            )
        );

        $query = $qb->getQuery();

        $result=$this->parseResultSet($query->getResultSet());

        return $result;
    }

    /**
     * @param $groupName
     * @returns array
     */
    public function remove($groupName)
    {
        $qb = $this->gm->createQueryBuilder();

            $qb ->match('(g:Group{groupName:{groupName}})')
                ->optionalMatch('(g)-[r]-()')
                ->delete('r,g');

            $qb->setParameters(
                array(
                    'groupName' => $groupName
                )
            );

        $query = $qb->getQuery();
        return $this->parseResultSet($query->getResultSet());
        
    }

    /**
     * @param int $groupId
     * @throws \Exception
     * @return array
     */
    public function getAll()
    {

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(g:Group)')
            ->returns('g');

        $query = $qb->getQuery();
        return $this->parseResultSet($query->getResultSet());
    }

    /**
     * Gets all data from a group with its id
     * @param $groupId
     * @throws \Exception
     * @return array
     */
    public function getById($groupId)
    {

        $groupId=(integer)$groupId;
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(g:Group)')
           ->where('id(g)={groupId}')
           ->returns('g');
       
        $qb->setParameters(
            array(
                'groupId' => $groupId
            )
        );

        $query = $qb->getQuery();
        return $this->parseResultSet($query->getResultSet());
    }

    /**
     * This method accepts returning multiple groups
     * @param $groupName
     * @throws \Exception
     * @return array
     */
    public function getByName($groupName)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(g:Group{groupName:{groupName}})')
           ->returns('g');
       
        $qb->setParameters(
            array(
                'groupName' => $groupName
            )
        );

        $query = $qb->getQuery();
        return $this->parseResultSet($query->getResultSet());
    }

     /**
     * @param integer $userId
     * @throws \Exception
     * @return array
     */
    public function getByUser($userId){

        $qb = $this->gm->createQueryBuilder();
        $qb ->match('(u:User{qnoow_id:{userId}})')
            ->match('(u)-[r:BELONGS_TO]->(g:Group)')
            ->returns('g');
        $qb ->setParameters(
            array(
                'userId'=>$userId
            )
        );

        $query = $qb->getQuery();
        return $this->parseResultSet($query->getResultSet());
    }

     /**
     * @param array $data
     * @throws \Exception
     * @return array
     */
    public function addUserToGroup(array $data)
    {
        $qb = $this->gm->createQueryBuilder();
            
            $qb ->match('(g:Group{groupName:{groupName}})')
                ->match('(u:User{qnoow_id:{userId}})')
                ->create('(u)-[r:BELONGS_TO]->(g)')
                ->set('r.created=timestamp()');
            $qb ->returns('r');
           
            $qb->setParameters(
                array(
                    'groupName'   => $data['groupName'],
                    'userId'      => $data['id']
                )
            );

            $query = $qb->getQuery();
            $result=$query->getResultSet();

        return $result;
    }

    /**
     * @param $groupName
     * @param $id
     * @return bool
     * @throws \Exception
     */

    public function removeUserFromGroup($groupName,$id){

        $qb = $this->gm->createQueryBuilder();

            $qb ->match('(g:Group{groupName:{groupName}})')
                ->match('(u:User{qnoow_id:{userId}})')
                ->match('(u)-[r:BELONGS_TO]->(g)')
                ->delete('r');
           
            $qb->setParameters(
                array(
                    'groupName'   => $groupName,
                    'userId'    => (int)$id
                )
            );

            $query = $qb->getQuery();
            $result=$query->getResultSet();

        return $result;
    }

     /**
     * @param $groupName
     * @throws \Exception
     * @return boolean
     */
    public function isAlreadyCreated($groupName)
    {
        if (count($this->getByName($groupName))>0){
            return true;
        } else {
            return false;
        }
       
    }

    /**
     * Check if a given user belongs to a given group
     * @param $groupName
     * @param $id
     * @return bool
     */
    public function isUserFromGroup($groupName,$id)
    {
        if (!$groupName) return true;

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(g:Group{groupName:{groupName}})')
           ->match('(u:User{qnoow_id:{userId}})')
           ->match('(u)-[r:BELONGS_TO]->(g)')
           ->returns('r');
       
        $qb->setParameters(
            array(
                'groupName'   => $groupName,
                'userId'    => (int)$id
            )
        );

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if ($result->count() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Gets data from a resultSet of groups: Id and name of each one
     * @param $resultSet
     * @return array
     */
    private function parseResultSet($resultSet)
    {
        $groups = array();

        foreach ($resultSet as $row) {
            $group = array(
                'groupName' => $row['g']->getProperty('groupName'),
            );
            $groups[] = $group;
        }

        return $groups;

    }

}
