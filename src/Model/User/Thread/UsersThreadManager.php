<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace Model\User\Thread;


use Model\Neo4j\GraphManager;
use Model\User\Filters\FilterUsersManager;
use Manager\UserManager;

class UsersThreadManager
{

    /** @var  GraphManager */
    protected $graphManager;

    /** @var UserManager */
    protected $userManager;

    /** @var FilterUsersManager */
    protected $filterUsersManager;

    public function __construct(GraphManager $gm, FilterUsersManager $filterUsersManager, UserManager $userManager)
    {
        $this->graphManager = $gm;
        $this->userManager = $userManager;
        $this->filterUsersManager = $filterUsersManager;
    }

    public function getFilterUsersManager(){
        return $this->filterUsersManager;
    }

    /**
     * @param $id
     * @param $name
     * @return UsersThread
     */
    public function buildUsersThread($id, $name)
    {

        $thread = new UsersThread($id, $name);
        $filters = $this->filterUsersManager->getFilterUsersByThreadId($thread->getId());
        $thread->setFilterUsers($filters);

        return $thread;
    }

    public function update($id, array $filters)
    {
        return $this->filterUsersManager->updateFilterUsersByThreadId($id, $filters);
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
            $cached[] = $this->userManager->build($row);
        }

        return $cached;
    }


}