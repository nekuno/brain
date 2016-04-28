<?php
/**
 * Created by PhpStorm.
 * User: yawmoght
 */

namespace Service;


use Model\User\GroupModel;
use Model\User\Recommendation\ContentRecommendationPaginatedModel;
use Model\User\Recommendation\SocialUserRecommendationPaginatedModel;
use Model\User\Recommendation\UserRecommendationPaginatedModel;
use Model\User\Thread\ContentThread;
use Model\User\Thread\Thread;
use Model\User\Thread\UsersThread;
use Manager\UserManager;
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
    /** @var  $socialUserRecommendationPaginatedModel SocialUserRecommendationPaginatedModel */
    protected $socialUserRecommendationPaginatedModel;
    /** @var  $contentRecommendationPaginatedModel ContentRecommendationPaginatedModel */
    protected $contentRecommendationPaginatedModel;

    //TODO: Check if user can be passed as argument and remove this dependency
    /** @var  $userManager UserManager */
    protected $userManager;

    /**
     * Recommendator constructor.
     * @param Paginator $paginator
     * @param ContentPaginator $contentPaginator
     * @param GroupModel $groupModel
     * @param UserManager $userManager
     * @param UserRecommendationPaginatedModel $userRecommendationPaginatedModel
     * @param SocialUserRecommendationPaginatedModel $socialUserRecommendationPaginatedModel
     * @param ContentRecommendationPaginatedModel $contentRecommendationPaginatedModel
     */
    public function __construct(Paginator $paginator,
                                ContentPaginator $contentPaginator,
                                GroupModel $groupModel,
                                UserManager $userManager,
                                UserRecommendationPaginatedModel $userRecommendationPaginatedModel,
                                SocialUserRecommendationPaginatedModel $socialUserRecommendationPaginatedModel,
                                ContentRecommendationPaginatedModel $contentRecommendationPaginatedModel)
    {
        $this->paginator = $paginator;
        $this->contentPaginator = $contentPaginator;
        $this->groupModel = $groupModel;
        $this->userManager = $userManager;
        $this->userRecommendationPaginatedModel = $userRecommendationPaginatedModel;
        $this->socialUserRecommendationPaginatedModel = $socialUserRecommendationPaginatedModel;
        $this->contentRecommendationPaginatedModel = $contentRecommendationPaginatedModel;
    }

    public function getRecommendationFromThread(Thread $thread)
    {
        $user = $this->userManager->getOneByThread($thread->getId());
        //Todo: Change to Class::class if PHP >= 5.5
        switch (get_class($thread)) {
            case 'Model\User\Thread\ContentThread':

                /* @var $thread ContentThread */
                $threadFilters = $thread->getFilterContent();
                $filters = array('id' => $user->getId());

                if ($threadFilters->getTag()) {
                    foreach ($threadFilters->getTag() as $singleTag){
                        $filters['tag'][] = urldecode($singleTag);
                    }
                }

                foreach($threadFilters->getType() as $type){
                    $filters['type'][] = urldecode($type);
                }

                return $this->getContentRecommendation($filters);

                break;
            case 'Model\User\Thread\UsersThread':
                /* @var $thread UsersThread */
                $threadFilters = $thread->getFilterUsers();
                $filters = array(
                    'id' => $user->getId(),
                    'profileFilters' => $threadFilters->getProfileFilters(),
                    'userFilters' => $threadFilters->getUserFilters(),
                );

                return $this->getUserRecommendation($filters);

                break;
            default:
                $recommendation = array();
                break;
        }

        return array(
            'thread' => $thread,
            'recommendation' => $recommendation);
    }

    public function getRecommendationFromThreadAndRequest(Thread $thread, Request $request)
    {
        $user = $this->userManager->getOneByThread($thread->getId());
        //Todo: Change to Class::class if PHP >= 5.5
        switch (get_class($thread)) {
            case 'Model\User\Thread\ContentThread':

                /* @var $thread ContentThread */

                $filters = array('id' => $user->getId());
                $threadFilters = $thread->getFilterContent();

                if ($threadFilters->getTag()) {
                    foreach ($threadFilters->getTag() as $singleTag){
                        $filters['tag'][] = urldecode($singleTag);
                    }
                }

                foreach($threadFilters->getType() as $type){
                    $filters['type'][] = urldecode($type);
                }


                if ($request->get('foreign')) {
                    $filters['foreign'] = urldecode($request->get('foreign'));
                }

                return $this->getContentRecommendation($filters, $request);

                break;
            case 'Model\User\Thread\UsersThread':
                /* @var $thread UsersThread */
                $order = $request->get('order', false);

                /* @var $thread UsersThread */
                $threadFilters = $thread->getFilterUsers();
                $filters = array(
                    'id' => $user->getId(),
                    'profileFilters' => $threadFilters->getProfileFilters(),
                    'userFilters' => $threadFilters->getUserFilters(),
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
     * @param bool $social Whether the Request comes from Social
     * @return array
     */
    public function getUserRecommendationFromRequest(Request $request, $id, $social =false)
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

        return $this->getUserRecommendation($filters, $request, $social);
    }

    public function getContentRecommendationFromRequest(Request $request, $id)
    {

        //TODO: Validate

        $tag = $request->get('tag', array());
        $type = $request->get('type', array());
        $foreign = $request->get('foreign', null);

        $filters = array('id' => $id);

        foreach ($tag as $singleTag) {
            $filters['tag'][] = urldecode($singleTag);
        }

        foreach ($type as $singleType) {
            $filters['type'][] = urldecode($singleType);
        }

        if ($foreign) {
            $filters['foreign'] = urldecode($foreign);
        }

        return $this->getContentRecommendation($filters, $request);

    }

    /**
     * @param $filters
     * @param null $request
     * @param bool $social
     * @return array
     */
    private function getUserRecommendation($filters, $request = null, $social = false)
    {
        if ($request == null){
            $request = new Request();
        }
        //TODO: Move to userRecommendationPaginatedModel->validate($filters)
        if (isset($filters['userFilters']['groups']) && null !== $filters['userFilters']['groups']) {
            foreach ($filters['userFilters']['groups'] as $group) {
                if (!$this->groupModel->isUserFromGroup($group, $filters['id'])) {
                    throw new AccessDeniedHttpException(sprintf('Not allowed to filter on group "%s"', $group));
                }
            }
        }

        $model = $social ? $this->socialUserRecommendationPaginatedModel : $this->userRecommendationPaginatedModel;

        $result = $this->paginator->paginate($filters, $model, $request);
        return $result;
    }

    private function getContentRecommendation($filters, $request = null)
    {
        if ($request == null){
            $request = new Request();
        }

        $result = $this->contentPaginator->paginate($filters, $this->contentRecommendationPaginatedModel, $request);

        return $result;
    }

}