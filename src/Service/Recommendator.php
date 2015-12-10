<?php
/**
 * Created by PhpStorm.
 * User: yawmoght
 */

namespace Service;


use Model\User\GroupModel;
use Model\User\Recommendation\ContentRecommendationPaginatedModel;
use Model\User\Recommendation\UserRecommendationPaginatedModel;
use Model\User\Thread\ContentThread;
use Model\User\Thread\Thread;
use Model\User\Thread\UsersThread;
use Model\UserModel;
use Paginator\ContentPaginator;
use Paginator\Paginator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class Recommendator
{
    /** @var  $paginator Paginator */
    protected $paginator;
    /** @var  $contentPaginator ContentPaginator */
    protected $contentPaginator;
    /** @var  $groupModel GroupModel */
    protected $groupModel;
    /** @var  $userRecommendationPaginatedModel UserRecommendationPaginatedModel */
    protected $userRecommendationPaginatedModel;
    /** @var  $contentRecommendationPaginatedModel ContentRecommendationPaginatedModel */
    protected $contentRecommendationPaginatedModel;
    /** @var  $userModel UserModel */
    protected $userModel;

    /**
     * Recommendator constructor.
     * @param Paginator $paginator
     * @param ContentPaginator $contentPaginator
     * @param GroupModel $groupModel
     * @param UserModel $userModel
     * @param UserRecommendationPaginatedModel $userRecommendationPaginatedModel
     * @param ContentRecommendationPaginatedModel $contentRecommendationPaginatedModel
     */
    public function __construct(Paginator $paginator, ContentPaginator $contentPaginator, GroupModel $groupModel, UserModel $userModel, UserRecommendationPaginatedModel $userRecommendationPaginatedModel, ContentRecommendationPaginatedModel $contentRecommendationPaginatedModel)
    {
        $this->paginator = $paginator;
        $this->contentPaginator = $contentPaginator;
        $this->groupModel = $groupModel;
        $this->userModel = $userModel;
        $this->userRecommendationPaginatedModel = $userRecommendationPaginatedModel;
        $this->contentRecommendationPaginatedModel = $contentRecommendationPaginatedModel;
    }

    public function getRecommendationFromThread(Thread $thread, Request $request)
    {
        $user = $this->userModel->getOneByThread($thread->getId());
        //Todo: Change to Class::class if PHP >= 5.5
        switch (get_class($thread)) {
            case 'Model\User\Thread\ContentThread':

                /* @var $thread ContentThread */

                $filters = array('id' => $user['qnoow_id']);

                if ($thread->getTag()) {
                    $filters['tag'] = urldecode($thread->getTag());
                }

                $filters['type'] = urldecode($thread->getType());

                if ($request->get('foreign')) {
                    $filters['foreign'] = urldecode($request->get('foreign'));
                }

                return $this->getContentRecommendation($filters, $request);

                break;
            case 'Model\User\Thread\UsersThread':
                /* @var $thread UsersThread */
                $order = $request->get('order', false);

                $filters = array(
                    'id' => $user['qnoow_id'],
                    'profileFilters' => $thread->getProfileFilters(),
                    'userFilters' => $thread->getUserFilters(),
                );

                if ($order) {
                    $filters['order'] = $order;
                }


                return $this->getUserRecommendation($filters, $request);

                break;
            default:
                $recommendation = array();
                break;
        }

        return array(
            'thread' => $thread,
            'recommendation' => $recommendation);
    }

    /**
     * @param Request $request
     * @param integer $id userId
     * @return array
     * @throws AccessDeniedHttpException
     */
    public function getUserRecommendationFromRequest(Request $request, $id)
    {

        //TODO: Validate
        $order = $request->get('order', false);

        $filters = array(
            'id' => $id,
            'profileFilters' => $request->get('profileFilters', array()),
            'userFilters' => $request->get('userFilters', array()),
        );

        if ($order) {
            $filters['order'] = $order;
        }

        return $this->getUserRecommendation($filters, $request);
    }

    public function getContentRecommendationFromRequest(Request $request, $id)
    {

        //TODO: Validate

        $tag = $request->get('tag', null);
        $type = $request->get('type', null);
        $foreign = $request->get('foreign', null);

        $filters = array('id' => $id);

        if ($tag) {
            $filters['tag'] = urldecode($tag);
        }

        if ($type) {
            $filters['type'] = urldecode($type);
        }

        if ($foreign) {
            $filters['foreign'] = urldecode($foreign);
        }

        return $this->getContentRecommendation($filters, $request);

    }

    private function getUserRecommendation($filters, $request)
    {
        //TODO: Move to userRecommendationPaginatedModel->validate($filters)
        if (isset($filters['userFilters']['groups']) && null !== $filters['userFilters']['groups']) {
            foreach ($filters['userFilters']['groups'] as $group) {
                if (!$this->groupModel->isUserFromGroup($group, $filters['id'])) {
                    throw new AccessDeniedHttpException(sprintf('Not allowed to filter on group "%s"', $group));
                }
            }
        }

        $result = $this->paginator->paginate($filters, $this->userRecommendationPaginatedModel, $request);
var_dump($filters);
        return $result;
    }

    private function getContentRecommendation($filters, $request)
    {
        $result = $this->contentPaginator->paginate($filters, $this->contentRecommendationPaginatedModel, $request);

        return $result;
    }

}