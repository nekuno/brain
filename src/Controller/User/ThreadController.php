<?php
/**
 * @author Roberto M. Pallarola <yawmoght@gmail.com>
 */

namespace Controller\User;

use Model\User\Thread\ThreadPaginatedModel;
use Model\User;
use Paginator\Paginator;
use Service\Recommendator;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class ThreadController
{
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
        $thread = $app['users.threads.manager']->getById($id);

        $result = $this->getRecommendations($app, $thread, $request);
        if (!is_array($result)) {
            return $app->json($result, 500);
        }

        return $app->json($result);
    }

    /**
     * Get threads from a given user
     * @param Application $app
     * @param Request $request
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getByUserAction(Application $app, Request $request, User $user)
    {
        $filters = array(
            'userId' => $user->getId()
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
     * @param User $user
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function postAction(Application $app, Request $request, User $user)
    {
        $thread = $app['users.threads.manager']->create($user->getId(), $request->request->all());

        $this->getRecommendations($app, $thread, $request);

        return $app->json($thread, 201);
    }

    public function createDefaultAction(Application $app, User $user)
    {
        $threadManager = $app['users.threads.manager'];

        $threads = $threadManager->getDefaultThreads($user);
        try{
            $createdThreads = $threadManager->createBatchForUser($user->getId(), $threads);
        } catch (\Exception $e) {
            sleep(5);
            $createdThreads = $threadManager->createBatchForUser($user->getId(), $threads);
        }

        if (count($createdThreads) < count ($threads) ) {
            sleep(5);
            $createdThreads = $threadManager->createBatchForUser($user->getId(), $threads);
        }

        return $app->json($createdThreads, 201);
    }

    /**
     * @param Application $app
     * @param Request $request
     * @param User $user
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function putAction(Application $app, Request $request, User $user, $id)
    {
        $thread = $app['users.threads.manager']->update($id, $user->getId(), $request->request->all());

        $this->getRecommendations($app, $thread, $request);

        $thread = $app['users.threads.manager']->getById($thread->getId());

        return $app->json($thread, 201);
    }

    /**
     * @param Application $app
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function deleteAction(Application $app, $id)
    {
        try {
            $relationships = $app['users.threads.manager']->deleteById($id);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json($e->getMessage(), 500);
        }

        return $app->json($relationships);
    }

    /**
     * @param $app
     * @param $thread
     * @param Request $request
     * @return array|string string if got an exception in production environment
     * @throws \Exception
     */
    protected function getRecommendations($app, $thread, Request $request) {
        /** @var Recommendator $recommendator */
        $recommendator = $app['recommendator.service'];
        try {
            $result = $recommendator->getRecommendationFromThreadAndRequest($thread, $request);

            if (!$request->get('offset')){
                $app['users.threads.manager']->cacheResults($thread,
                    array_slice($result['items'], 0, 20),
                    $result['pagination']['total']);
            }

        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            } else {
                return $e->getMessage();

            }
        }

        return $result;
    }
}