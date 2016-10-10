<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace Model\User\Thread;

use Model\Neo4j\GraphManager;
use Model\User\Filters\FilterUsersManager;
use Manager\UserManager;
use Model\User\Recommendation\UserRecommendationPaginatedModel;

class UsersThreadManager
{
    /** @var  GraphManager */
    protected $graphManager;

    /** @var UserManager */
    protected $userManager;

    /** @var FilterUsersManager */
    protected $filterUsersManager;

    /** @var UserRecommendationPaginatedModel */
    protected $userRecommendationPaginatedModel;

    public function __construct(GraphManager $gm, FilterUsersManager $filterUsersManager, UserManager $userManager, UserRecommendationPaginatedModel $urpm)
    {
        $this->graphManager = $gm;
        $this->userManager = $userManager;
        $this->filterUsersManager = $filterUsersManager;
        $this->userRecommendationPaginatedModel = $urpm;
    }

    public function getFilterUsersManager()
    {
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
            ->with('thread')
            ->match('(thread)<-[:HAS_THREAD]-(owner:User)')
            ->with('thread', 'owner')
            ->match('(thread)-[:RECOMMENDS]->(u:User)')
            ->with('owner', 'u')
            ->optionalMatch('(owner)-[like:LIKES]->(u)')
            ->with('owner', 'u', '(CASE WHEN like IS NOT NULL THEN 1 ELSE 0 END) AS like')
            ->optionalMatch('(owner)-[s:SIMILARITY]-(u)')
            ->with('owner', 's.similarity as similarity', 'u', 'like')
            ->optionalMatch('(owner)-[m:MATCHES]-(u)')
            ->with('similarity, u, m.matching_questions AS matching_questions', 'like')
            ->match('(u)<-[:PROFILE_OF]-(p:Profile)')
            ->with('similarity', 'matching_questions', 'p', 'like', 'u')
            ->optionalMatch('(p)-[:LOCATION]-(l:Location)')
            ->returns(
                'similarity',
                'matching_questions',
                'u.qnoow_id AS id',
                'u.username AS username',
                'u.photo AS photo',
                'p.birthday AS birthday',
                'l.locality + ", " + l.country AS location',
                'like'
            );
        $qb->setParameters($parameters);
        $result = $qb->getQuery()->getResultSet();

        return $this->userRecommendationPaginatedModel->buildUserRecommendations($result);
    }

}