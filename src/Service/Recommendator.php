<?php

namespace Service;


use Model\User\Group\GroupModel;
use Model\User\Recommendation\ContentPopularRecommendationPaginatedModel;
use Model\User\Recommendation\ContentRecommendationPaginatedModel;
use Model\User\Recommendation\SocialUserRecommendationPaginatedModel;
use Model\User\Recommendation\UserPopularRecommendationPaginatedModel;
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
    /** @var $contentPopularRecommendationPaginatedModel ContentPopularRecommendationPaginatedModel */
    protected $contentPopularRecommendationPaginatedModel;

    //TODO: Check if user can be passed as argument and remove this dependency
    /** @var  $userManager UserManager */
    protected $userManager;
    protected $userPopularRecommendationPaginatedModel;

    /**
     * Recommendator constructor.
     * @param Paginator $paginator
     * @param ContentPaginator $contentPaginator
     * @param GroupModel $groupModel
     * @param UserManager $userManager
     * @param UserRecommendationPaginatedModel $userRecommendationPaginatedModel
     * @param SocialUserRecommendationPaginatedModel $socialUserRecommendationPaginatedModel
     * @param ContentRecommendationPaginatedModel $contentRecommendationPaginatedModel
     * @param UserPopularRecommendationPaginatedModel $userPopularRecommendationPaginatedModel
     * @param ContentPopularRecommendationPaginatedModel $contentPopularRecommendationPaginatedModel
     */
    public function __construct(Paginator $paginator,
                                ContentPaginator $contentPaginator,
                                GroupModel $groupModel,
                                UserManager $userManager,
                                UserRecommendationPaginatedModel $userRecommendationPaginatedModel,
                                SocialUserRecommendationPaginatedModel $socialUserRecommendationPaginatedModel,
                                ContentRecommendationPaginatedModel $contentRecommendationPaginatedModel,
                                UserPopularRecommendationPaginatedModel $userPopularRecommendationPaginatedModel,
                                ContentPopularRecommendationPaginatedModel $contentPopularRecommendationPaginatedModel)
    {
        $this->paginator = $paginator;
        $this->contentPaginator = $contentPaginator;
        $this->groupModel = $groupModel;
        $this->userManager = $userManager;
        $this->userRecommendationPaginatedModel = $userRecommendationPaginatedModel;
        $this->socialUserRecommendationPaginatedModel = $socialUserRecommendationPaginatedModel;
        $this->contentRecommendationPaginatedModel = $contentRecommendationPaginatedModel;
        $this->contentPopularRecommendationPaginatedModel = $contentPopularRecommendationPaginatedModel;
        $this->userPopularRecommendationPaginatedModel = $userPopularRecommendationPaginatedModel;
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

                if ($user->isGuest())
                {
                    return $this->getPopularContentRecommendation($filters);
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

                if ($user->isGuest())
                {
                    return $this->getPopularUserRecommendation($filters);
                }

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

                if ($request->get('ignored')) {
                    $filters['ignored'] = urldecode($request->get('ignored'));
                }

                if ($user->isGuest())
                {
                    return $this->getPopularContentRecommendation($filters, $request);
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

                if ($request->get('foreign')) {
                    $filters['foreign'] = urldecode($request->get('foreign'));
                }

                if ($request->get('ignored')) {
                    $filters['ignored'] = urldecode($request->get('ignored'));
                }

                if ($user->isGuest())
                {
                    return $this->getPopularUserRecommendation($filters, $request);
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
    public function getUserRecommendationFromRequest(Request $request, $id, $social = false)
    {

        //TODO: Validate
        $order = $request->get('order', false);
        $ignored = $request->get('ignored', null);
        $foreign = $request->get('foreign', null);

        $filters = array(
            'id' => $id,
            'profileFilters' => $request->get('profileFilters', array()),
            'userFilters' => $request->get('userFilters', array()),
        );

        if ($order) {
            $filters['order'] = $order;
        }

        if ($foreign) {
            $filters['foreign'] = urldecode($foreign);
        }

        if ($ignored) {
            $filters['ignored'] = urldecode($ignored);
        }

        if (!$social && $this->userManager->getById($id)->isGuest()) {
            return $this->getPopularUserRecommendation($filters);
        }

        return $this->getUserRecommendation($filters, $request, $social);
    }

    public function getContentRecommendationFromRequest(Request $request, $id)
    {

        //TODO: Validate

        $tag = $request->get('tag', array());
        $type = $request->get('type', array());
        $foreign = $request->get('foreign', null);
        $ignored = $request->get('ignored', null);

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

        if ($ignored) {
            $filters['ignored'] = urldecode($ignored);
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
        $request = $this->getRequest($request);
        //TODO: Move to userRecommendationPaginatedModel->validate($filters)
        if (isset($filters['userFilters']['groups']) && null !== $filters['userFilters']['groups']) {
            foreach ($filters['userFilters']['groups'] as $group) {
                if (!$this->groupModel->isUserFromGroup($group, $filters['id'])) {
                    throw new AccessDeniedHttpException(sprintf('Not allowed to filter on group "%s"', $group));
                }
            }
        }

        $model = $social ? $this->socialUserRecommendationPaginatedModel : $this->userRecommendationPaginatedModel;
        $paginator = $social ? $this->paginator : $this->contentPaginator;

        $result = $paginator->paginate($filters, $model, $request);
        return $result;
    }

    private function getContentRecommendation($filters, $request = null)
    {
        $request = $this->getRequest($request);

        $result = $this->contentPaginator->paginate($filters, $this->contentRecommendationPaginatedModel, $request);

        return $result;
    }

    private function getPopularContentRecommendation($filters, $request = null)
    {
        $request = $this->getRequest($request);

        return $this->paginator->paginate($filters, $this->contentPopularRecommendationPaginatedModel , $request);
    }

    private function getPopularUserRecommendation($filters, $request = null)
    {
        $request = $this->getRequest($request);
        //TODO: Move to userPopularRecommendationPaginatedModel->validate($filters)
        if (isset($filters['userFilters']['groups']) && null !== $filters['userFilters']['groups']) {
            foreach ($filters['userFilters']['groups'] as $group) {
                if (!$this->groupModel->isUserFromGroup($group, $filters['id'])) {
                    throw new AccessDeniedHttpException(sprintf('Not allowed to filter on group "%s"', $group));
                }
            }
        }

        return $this->paginator->paginate($filters, $this->userPopularRecommendationPaginatedModel , $request);
    }

    private function getRequest(Request $request = null)
    {
        return $request ?: new Request();
    }

}