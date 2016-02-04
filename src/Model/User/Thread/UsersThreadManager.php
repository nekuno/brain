<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace Model\User\Thread;


use Model\Neo4j\GraphManager;
use Model\User\Filters\FilterManager;
use Model\UserModel;

class UsersThreadManager
{

    /** @var  GraphManager */
    protected $graphManager;

    /** @var UserModel */
    protected $userModel;

    /** @var FilterManager */
    protected $filterManager;

    public function __construct(GraphManager $gm, FilterManager $filterManager, UserModel $userModel)
    {
        $this->graphManager = $gm;
        $this->userModel = $userModel;
        $this->filterManager = $filterManager;
    }

    /**
     * @param $id
     * @param $name
     * @return UsersThread
     */
    public function buildUsersThread($id, $name)
    {

        $thread = new UsersThread($id, $name);
        $filters = $this->filterManager->getFilterUsersByThreadId($thread->getId());
        $thread->setFilterUsers($filters);

        return $thread;
    }

    public function update($id, array $filters)
    {
        return $this->filterManager->updateFilterUsersByThreadId($id, $filters);
    }

    public function getCached(Thread $thread)
    {
        $parameters = array(
            'id' => $thread->getId(),
        );

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}')
            ->match('(thread)-[:RECOMMENDS]->(u:User)')
            ->returns('u');
        $qb->setParameters($parameters);
        $result = $qb->getQuery()->getResultSet();

        $cached = array();
        foreach ($result as $row) {
            $cached[] = $this->userModel->build($row);
        }

        return $cached;
    }


}