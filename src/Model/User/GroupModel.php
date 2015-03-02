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
     * @param GraphManager gm
     */
    public function __construct(GraphManager $gm)
    {

        $this->gm = $gm;
    }

    /**
     * @param array $data
     * @throws \Exception
     * @return array
     */
    public function create(array $data)
    {
        $qb = $this->gm->createQueryBuilder();

        $result=array();


        if(this->isAlreadyCreated($groupId)){
            $result['alreadyCreated']=true;
            $qb ->match('(g:Group{id:{groupId}})')
                ->returns('g');
        } else {
            $result['alreadyCreated']=false;
            $qb ->create('(g:Group{id:{groupId},groupName:{groupName}})')
                ->returns('g');
        }
          
        $qb->setParameters(
            array(
                'groupId' => $data['groupId'],
                'groupName'=>$data['groupName']
            )
        );
        $query = $qb->getQuery();

        $resultArray=this->parseResultSet($query->getResultSet());

        $result=array_merge($result,$resultArray);

        return $result;
    }
    /*
     * @param int $groupId
     * @returns array
     */
    public function remove(int $groupId)
    {
        $qb = $this->gm->createQueryBuilder();

        $result=array();


        if(this->isAlreadyCreated($groupId)){
            $result['wasCreated']=true;

            //needs to delete all relationships too
            $qb ->match('(g:Group{id:{groupId}})')
                ->match('(g)-[r]-()')
                ->delete('r,g');

            $qb->setParameters(
                array(
                    'groupId' => $groupId
                )
            );
            
        } else {
            $result['wasCreated']=false;
        }
          
        return $result;
        
    }

    /**
     * Gets all data from a group with its id
     * @param int $groupId
     * @throws \Exception
     * @return array
     */
    public function getById(int $groupId)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(g:Group{id:{groupId}})')
           ->returns('g');
       
        $qb->setParameters(
            array(
                'groupId' => $groupId
            )
        );

        $query = $qb->getQuery();

        return this->parseResultSet($query->getResultSet());
    }

    /**
     * Gets all data from a group with its name
     * This method accepts returning multiple groups
     * @param int $groupId
     * @throws \Exception
     * @return array
     */
    public function getByName(int $groupName)
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

        return this->parseResultSet($query->getResultSet());
    }

     /**
     * @param $groupId
     * @param $userId
     * @throws \Exception
     * @return array
     */
    public function addUserToGroup($data)
    {
        $qb = $this->gm->createQueryBuilder();

        $result=array();

        if (isUserFromGroup($groupId,$userId)){
            $result['wasBelonging']=true;
        } else {
            
            $qb ->match('(g:Group{id:{groupId})')
                ->match('(u:User{id:{userId}}')
                ->match('(u)-[r:BELONGS_TO]->(g)')
                ->set('r.created=timestamp()');
            $qb ->returns('r');
           
            $qb->setParameters(
                array(
                    'groupId'   => $data['groupId'],
                    'userId'    => $data['userId']
                )
            );

            $query = $qb->getQuery();
            $result=$query->getResultSet();

            $result['wasBelonging']=false;
        }

        return $result;
    }

    /**
     * @param array data
     * @throws \Exception
     * @return boolean
     */

    public function removeUserFromGroup(array $data){

        $qb = $this->gm->createQueryBuilder();

        $result=array();

        if (isUserFromGroup($groupId,$userId)){
            
            $qb ->match('(g:Group{id:{groupId})')
                ->match('(u:User{id:{userId}}')
                ->match('(u)-[r:BELONGS_TO]->(g)')
                ->delete('r');
           
            $qb->setParameters(
                array(
                    'groupId'   => $data['groupId'],
                    'userId'    => $data['userId']
                )
            );

            $query = $qb->getQuery();
            $result=$query->getResultSet();

            $result['wasBelonging']=true;

        } else {
            
            $result['wasBelonging']=false;
        }

        return $result;
    }

     /**
     * Check if a given group already exists
     * @param $groupId
     * @throws \Exception
     * @return boolean
     */
    public function isAlreadyCreated($groupId)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(g:Group{id:{groupId})');
        $qb->returns('g');
       
        $qb->setParameters(
            array(
                'groupId' => $groupId
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
     * Check if a given user belongs to a given group
     * @param $groupId
     * @throws \Exception
     * @return boolean
     */
    public function isUserFromGroup($groupId,$userId)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(g:Group{id:{groupId})');
           ->match('(u:User{id:{userId}})')
           ->match('(u)-[r:BELONGS_TO]->(g)')
           ->returns('r');
       
        $qb->setParameters(
            array(
                'groupId'   => $groupId,
                'userId'    => $userId
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
                'group_Id' => $row['g']->getProperty('id'),
                'groupName' => $row['g']->getProperty('groupName'),
            );
            $group[] = $group;
        }

        return $groups;

    }

}
