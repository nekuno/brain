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

    /**
     * Create new thread for a given user
     * @param Application $app
     * @param Request $request
     * @param string $id Qnoow_id of the user creating the thread
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function postAction(Application $app, Request $request, $id)
    {

        $thread = $this->threadManager->create($id, $request->request->all());

        return $app->json($thread, 201);
    }

    public function putAction(Application $app, Request $request)
    {
        //separate filters by type

        //update thread (profileFilters, userFilters)

        //return result
    }

    public function deleteAction(Application $app, $id)
    {
        //delete thread (id)

        //return result
    }

    public function getUsersAction(Application $app, $id)
    {

        //threadManager -> getUsers(id) (calls userRecommendationPaginatedModel inside)

        //return result (already paginated)
    }

    public function getContentAction(Application $app, $id)
    {

        //threadManager -> getContent(id) (calls contentRecommendationPaginatedModel inside)

        //return result
    }
}