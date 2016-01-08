<?php
/**
 * @author Roberto M. Pallarola <yawmoght@gmail.com>
 */

namespace Controller\User;

use Model\User\Thread\ThreadManager;
use Model\User\Thread\ThreadPaginatedModel;
use Paginator\Paginator;
use Service\Recommendator;
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
     * Parameters accepted when ContentThread:
     * -offset, limit and foreign
     * Parameters accepted when UsersThread:
     * -order
     *
     * @param Application $app
     * @param Request $request
     * @param string $id threadId
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getRecommendationAction(Application $app, Request $request, $id)
    {

        $thread = $this->threadManager->getById($id);

        /** @var Recommendator $recommendator */
        $recommendator = $app['recommendator.service'];

        try {
            $result = $recommendator->getRecommendationFromThreadAndRequest($thread, $request);

            if ($request->get('offset') == 0) {
                $this->threadManager->cacheResults($thread,
                    array_slice($result['items'], 0, 5),
                    $result['pagination']['total']);
            }

        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json($e->getMessage(), 500);
        }

        return $app->json($result);
    }

    /**
     * Get threads from a given user
     * @param Application $app
     * @param Request $request
     * @param int $id user id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getByUserAction(Application $app, Request $request, $id)
    {

        $filters = array(
            'userId' => $id
        );

        /** @var Paginator $paginator */
        $paginator = $app['paginator'];

        /** @var ThreadPaginatedModel $model */
        $model = $app['users.threads.paginated.model'];

        $result = $paginator->paginate($filters, $model, $request);

        return $app->json($result);
    }

    /**
     * Create new thread for a given user
     * @param Application $app
     * @param Request $request
     * @param string $id Qnoow_id of the user creating the thread
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function postAction(Application $app, Request $request, $id)
    {

        $thread = $this->threadManager->create($id, $request->request->all());

        /** @var Recommendator $recommendator */
        $recommendator = $app['recommendator.service'];
        try {
            $result = $recommendator->getRecommendationFromThreadAndRequest($thread, $request);
            $this->threadManager->cacheResults($thread,
                array_slice($result['items'], 0, 5),
                $result['pagination']['total']);

            $thread = $this->threadManager->getById($thread->getId());
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json($e->getMessage(), 500);
        }
        return $app->json($thread, 201);
    }

    /**
     * @param Application $app
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function putAction(Application $app, Request $request, $id)
    {

        $thread = $this->threadManager->update($id, $request->request->all());

        /** @var Recommendator $recommendator */
        $recommendator = $app['recommendator.service'];

        try {
            $result = $recommendator->getRecommendationFromThreadAndRequest($thread, $request);

            $this->threadManager->cacheResults($thread,
                array_slice($result['items'], 0, 5),
                $result['pagination']['total']);

        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

        }

        $thread = $this->threadManager->getById($thread->getId());

        return $app->json($thread, 201);
    }

    public function deleteAction(Application $app, $id)
    {
        try {
            $relationships = $this->threadManager->deleteById($id);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json($e->getMessage(), 500);
        }


        return $app->json($relationships);
    }

}