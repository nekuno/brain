<?php
/**
 * @author Roberto M. Pallarola <yawmoght@gmail.com>
 */

namespace Controller\User;


use Model\User\GroupModel;
use Model\User\Thread\ContentThread;
use Model\User\Thread\ThreadManager;
use Model\User\Thread\UsersThread;
use Model\UserModel;
use Paginator\Paginator;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
        /** @var UserModel $userModel */
        $userModel = $app['users.model'];
        $user = $userModel->getOneByThread($thread->getId());
        //Todo: Change to Class::class if PHP >= 5.5
        switch (get_class($thread)) {
            case 'Model\User\Thread\ContentThread':

                /* @var $thread ContentThread */

                //TODO: Move logic to Recommendator

                /* @var $paginator \Paginator\ContentPaginator */
                $paginator = $app['paginator.content'];

                $filters = array('id' => $user['qnoow_id']);

                if ($thread->getTag()) {
                    $filters['tag'] = urldecode($thread->getTag());
                }

                $filters['type'] = urldecode($thread->getType());

                if ($request->get('foreign')) {
                    $filters['foreign'] = urldecode($request->get('foreign'));
                }

                /* @var $model \Model\User\Recommendation\ContentRecommendationPaginatedModel */
                $model = $app['users.recommendation.content.model'];

                $request->query->add(array(
                    ''
                ));
                try {
                    $recommendation = $paginator->paginate($filters, $model, $request);
                } catch (\Exception $e) {
                    if ($app['env'] == 'dev') {
                        throw $e;
                    }

                    return $app->json(array(), 500);
                }

                break;
            case 'Model\User\Thread\UsersThread':
                //TODO: Move logic to Recommendator

                /* @var $thread UsersThread */
                $order = $request->get('order', false);

                /* @var $paginator Paginator */
                $paginator = $app['paginator'];

                $filters = array(
                    'id' => $user['qnoow_id'],
                    'profileFilters' => $thread->getProfileFilters(),
                    'userFilters' => $thread->getUserFilters(),
                );

                if ($order) {
                    $filters['order'] = $order;
                }

                // Check neccesary in case thread is created and then user leaves group
                /* @var $groupModel GroupModel */
                $groupModel = $app['users.groups.model'];
                if (isset($filters['userFilters']['groups']) && null !== $filters['userFilters']['groups']) {
                    foreach ($filters['userFilters']['groups'] as $group) {
                        if (!$groupModel->isUserFromGroup($group, $id)) {
                            throw new AccessDeniedHttpException(sprintf('Not allowed to filter on group "%s"', $group));
                        }
                    }
                }

                /* @var $model \Model\User\Recommendation\UserRecommendationPaginatedModel */
                $model = $app['users.recommendation.users.model'];

                $recommendation = $paginator->paginate($filters, $model, $request);

                break;
            default:
                $recommendation = array();
                break;
        }

        return $app->json(array(
            'thread' => $thread,
            'recommendation' => $recommendation));
    }

    /**
     * Get threads from a given user
     * @param Application $app
     * @param int $id user id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getByUserAction(Application $app, $id)
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