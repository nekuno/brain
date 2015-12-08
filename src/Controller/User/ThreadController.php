<?php
/**
 * @author Roberto M. Pallarola <yawmoght@gmail.com>
 */

namespace Controller\User;


use Model\User\Thread\ThreadManager;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class ThreadController
{

    /**
     * @var ThreadManager
     */
    protected $threadManager;

    public function __construct(ThreadManager $threadManager)
    {
        $this->threadManager = $threadManager;
    }

    /**
     * Get threads from a given user
     * @param Application $app
     * @param int $id user id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getAction(Application $app, $id)
    {

        $threads = $this->threadManager->getByUser($id);

        return $app->json(array('threads' => $threads));
    }

    public function postAction(Application $app, Request $request)
    {
        $category = $request->get('category');
        if (!in_array($category, array(ThreadManager::LABEL_THREAD_USERS, ThreadManager::LABEL_THREAD_CONTENT)))
        {
            return $app->json('Category not valid', 400);
        }

        $thread = $this->threadManager->saveThread($category, $request->get('filters'));

        return $app->json($thread);
    }

    public function putAction(Application $app, Request $request)
    {
        //separate filters by type

        //update thread (profileFilters, userFilters)

        //return result
    }

    public function deleteAction (Application $app, $id)
    {
        //delete thread (id)

        //return result
    }

    public function getUsersAction (Application $app, $id)
    {

        //threadManager -> getUsers(id) (calls userRecommendationPaginatedModel inside)

        //return result (already paginated)
    }

    public function getContentAction (Application $app, $id)
    {

        //threadManager -> getContent(id) (calls contentRecomendationPaginatedModel inside)

        //return result
    }
}