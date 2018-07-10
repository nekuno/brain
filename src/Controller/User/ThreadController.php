<?php

namespace Controller\User;

use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\Thread\Thread;
use Model\Thread\ThreadPaginatedManager;
use Model\User\User;
use Nelmio\ApiDocBundle\Annotation\Security;
use Paginator\Paginator;
use Service\RecommendatorService;
use Service\ThreadService;
use Symfony\Component\HttpFoundation\Request;
use Swagger\Annotations as SWG;

class ThreadController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get recommendations by thread
     *
     * @Get("/threads/{threadId}/recommendation")
     * @param integer $threadId
     * @param User $user
     * @param Request $request
     * @param ThreadService $threadService
     * @param RecommendatorService $recommendatorService
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="offset",
     *      in="query",
     *      type="integer",
     *      default=0
     * )
     * @SWG\Parameter(
     *      name="limit",
     *      in="query",
     *      type="integer",
     *      default=20
     * )
     * @SWG\Parameter(
     *      name="foreign",
     *      in="query",
     *      type="integer",
     * )
     * @SWG\Parameter(
     *      name="order",
     *      in="query",
     *      type="string",
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns own recommendations by thread",
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Unknown exception",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="threads")
     */
    public function getRecommendationAction($threadId, User $user, Request $request, ThreadService $threadService, RecommendatorService $recommendatorService)
    {
        $thread = $threadService->getByThreadIdAndUserId($threadId, $user->getId());
        $result = $this->getRecommendations($user, $recommendatorService, $threadService, $thread, $request);

        if (!is_array($result)) {
            return $this->view($result, 500);
        }

        return $this->view($result, 200);
    }

    /**
     * Get threads
     *
     * @Get("/threads")
     * @param User $user
     * @param Request $request
     * @param Paginator $paginator
     * @param ThreadPaginatedManager $threadPaginatedManager
     * @param ThreadService $threadService
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="offset",
     *      in="query",
     *      type="integer",
     *      default=0
     * )
     * @SWG\Parameter(
     *      name="limit",
     *      in="query",
     *      type="integer",
     *      default=20
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns recommendations",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="threads")
     */
    public function getByUserAction(User $user, Request $request, Paginator $paginator, ThreadPaginatedManager $threadPaginatedManager, ThreadService $threadService)
    {
        $filters = array(
            'userId' => $user->getId()
        );

        $result = $paginator->paginate($filters, $threadPaginatedManager, $request);

        foreach ($result['items'] as $key=>$threadId){
            $thread = $threadService->getByThreadId($threadId);
            $result['items'][$key] = $thread;
        }

        return $this->view($result, 200);
    }

    /**
     * Create a thread
     *
     * @Post("/threads")
     * @param User $user
     * @param Request $request
     * @param ThreadService $threadService
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          @SWG\Property(property="name", type="string"),
     *          @SWG\Property(property="category", type="string"),
     *          @SWG\Property(property="default", type="boolean"),
     *          @SWG\Property(property="filters", type="object"),
     *          example={ "name" = "My thread", "category" = "ThreadUsers", "filters" = {} },
     *      )
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Returns created thread.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="threads")
     */
    public function postAction(User $user, Request $request, ThreadService $threadService)
    {
        $thread = $threadService->createThread($user->getId(), $request->request->all());

        return $this->view($thread, 201);
    }

    /**
     * Edits a thread
     *
     * @Put("/threads/{threadId}", requirements={"threadId"="\d+"})
     * @param integer $threadId
     * @param User $user
     * @param Request $request
     * @param ThreadService $threadService
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          @SWG\Property(property="name", type="string"),
     *          @SWG\Property(property="category", type="string"),
     *          @SWG\Property(property="filters", type="object"),
     *          example={ "name" = "My thread", "category" = "ThreadUsers", "filters" = {} },
     *      )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns edited thread.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="threads")
     */
    public function putAction($threadId, User $user, Request $request, ThreadService $threadService)
    {
        $threadService->getByThreadIdAndUserId($threadId, $user->getId());
        $thread = $threadService->updateThread($threadId, $user->getId(), $request->request->all());

        return $this->view($thread, 200);
    }

    // TODO: User cannot delete a thread. Otherwise, it must be checked that the user owns the thread
//    /**
//     * Deletes a thread
//     *
//     * @Delete("/threads/{threadId}", requirements={"threadId"="\d+"})
//     * @param integer $threadId
//     * @param ThreadService $threadService
//     * @return \FOS\RestBundle\View\View
//     * @SWG\Response(
//     *     response=200,
//     *     description="Returns deleted thread.",
//     * )
//     * @Security(name="Bearer")
//     * @SWG\Tag(name="threads")
//     */
//    public function deleteAction($threadId, ThreadService $threadService)
//    {
//        try {
//            $relationships = $threadService->deleteById($threadId);
//        } catch (\Exception $e) {
//
//            return $this->view($e->getMessage(), 500);
//        }
//
//        return $this->view($relationships, 200);
//    }

    /**
     * @param User $user
     * @param RecommendatorService $recommendatorService
     * @param ThreadService $threadService
     * @param $thread
     * @param Request $request
     * @return array|string string if got an exception in production environment
     * @throws \Exception
     */
    protected function getRecommendations(User $user, RecommendatorService $recommendatorService, ThreadService $threadService, Thread $thread, Request $request)
    {
        try {
            $result = $recommendatorService->getUserRecommendationFromThreadAndRequest($user, $thread, $request);
//            $this->cacheRecommendations($thread, $request, $result, $threadService);
        } catch (\Exception $e) {

            return $e->getMessage();
        }

        return $result;
    }

    protected function cacheRecommendations(Thread $thread, Request $request, array $result, ThreadService $threadService)
    {
        $isFirstPage = !$request->get('offset');
        if ($isFirstPage) {
            $firstResults = array_slice($result['items'], 0, 20);
            $threadService->cacheResults($thread, $firstResults, $result['pagination']['total']);
        }
    }
}