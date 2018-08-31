<?php

namespace Controller\User;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\Affinity\AffinityManager;
use Model\Content\ContentComparePaginatedManager;
use Model\Content\ContentPaginatedManager;
use Model\Content\ContentReportManager;
use Model\Content\ContentTagManager;
use Model\Device\Device;
use Model\Device\DeviceManager;
use Model\Exception\ErrorList;
use Model\Exception\ValidationException;
use Model\Matching\MatchingManager;
use Model\Rate\RateManager;
use Model\Recommendation\ContentRecommendationTagManager;
use Model\Similarity\SimilarityManager;
use Model\User\User;
use Model\User\UserManager;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Paginator\Paginator;
use Service\AuthService;
use Service\MetadataService;
use Service\RecommendatorService;
use Service\RegisterService;
use Service\UserService;
use Service\UserStatsService;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @RouteResource("User")
 */
class UserController extends FOSRestController implements ClassResourceInterface
{
    protected $supportEmails;

    protected $env;

    public function __construct($supportEmails, $env)
    {
        foreach ($supportEmails as $supportEmail) {
            if ($supportEmail) {
                $this->supportEmails[] = $supportEmail;
            }
        }
        $this->env = $env;
    }

    /**
     * Get own user.
     *
     * @Get("/users")
     * @param User $user
     * @param UserManager $userManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns own user",
     *     schema=@SWG\Schema(
     *         @SWG\Property(property="user", type="object", ref=@Model(type=\Model\User\User::class, groups={"User"}))
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Throws not found exception",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="users")
     */
    public function getAction(User $user, UserManager $userManager)
    {
        $user = $userManager->getById($user->getId())->jsonSerialize();

        return $this->view($user, 200);
    }

    /**
     * Get other user.
     *
     * @Get("/users/{slug}")
     * @param string $slug
     * @param UserManager $userManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns other user by slug",
     *     schema=@SWG\Schema(
     *         @SWG\Property(property="user", type="object", ref=@Model(type=\Model\User\User::class, groups={"User"}))
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Throws not found exception",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="users")
     */
    public function getOtherAction($slug, UserManager $userManager)
    {
        $userArray = $userManager->getBySlug($slug)->jsonSerialize();
        $userArray = $userManager->deleteOtherUserFields($userArray);

        if (empty($userArray)) {
            return $this->view($userArray, 404);
        }

        return $this->view($userArray, 200);
    }

    /**
     * Get other public user.
     *
     * @Get("/public/users/{slug}")
     * @param string $slug
     * @param UserService $userService
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns other user by slug",
     *     schema=@SWG\Schema(
     *         @SWG\Property(property="user", type="object", ref=@Model(type=\Model\User\User::class, groups={"User"}))
     *     )
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Throws not found exception",
     * )
     * @SWG\Tag(name="users")
     */
    public function getOtherPublicAction($slug, UserService $userService)
    {
        $userArray = $userService->getOtherPublic($slug);

        if (empty($userArray)) {
            return $this->view($userArray, 404);
        }

        return $this->view($userArray, 200);
    }

    /**
     * Register new user.
     *
     * @Post("/register")
     * @param Request $request
     * @param RegisterService $registerService
     * @param \Twig_Environment $twig
     * @param \Swift_Mailer $mailer
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          required={"user", "profile", "token", "oauth", "trackingData"},
     *          @SWG\Property(property="user", type="object", ref=@Model(type=\Model\User\User::class, groups={"User"})),
     *          @SWG\Property(property="profile", type="object", ref=@Model(type=\Model\Profile\Profile::class, groups={"Profile"})),
     *          @SWG\Property(property="token", type="string"),
     *          @SWG\Property(property="oauth", type="object", ref=@Model(type=\Model\Token\Token::class, groups={"Token"})),
     *          @SWG\Property(property="trackingData", type="object"),
     *      )
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Registers user",
     * )
     * @SWG\Tag(name="users")
     */
    public function postAction(Request $request, RegisterService $registerService, \Twig_Environment $twig, \Swift_Mailer $mailer)
    {
        try {
            $data = $request->request->all();
            if (!isset($data['user']) || !isset($data['profile']) || !isset($data['token']) || !isset($data['oauth']) || !isset($data['trackingData'])) {
                $this->throwRegistrationException('Bad format');
            }
            if (!is_array($data['user']) || !is_array($data['profile']) || !is_string($data['token']) || !is_array($data['oauth']) || !is_string($data['trackingData'])) {
                $this->throwRegistrationException('Bad format');
            }
            $user = $registerService->register($data['user'], $data['profile'], $data['token'], $data['oauth'], $data['trackingData']);
        } catch (\Exception $e) {
            $errorMessage = $this->exceptionMessagesToString($e);
            $message = new \Swift_Message();
            $message
                ->setSubject('Nekuno registration error')
                ->setFrom('enredos@nekuno.com', 'Nekuno')
                ->setTo($this->supportEmails)
                ->setContentType('text/html')
                ->setBody(
                    $twig->render(
                        'email-notifications/registration-error-notification.html.twig',
                        array(
                            'e' => $e,
                            'errorMessage' => $errorMessage,
                            'data' => json_encode($request->request->all()),
                        )
                    )
                );

            $mailer->send($message);

            $exceptionMessage = $this->env === 'dev' ? $errorMessage . ' ' . $e->getFile() . ' ' . $e->getLine() : "Error registering user";
            $this->throwRegistrationException($exceptionMessage);
            return null;
        }

        return $this->view($user, 201);
    }

    /**
     * Edit a user.
     *
     * @Put("/users")
     * @param Request $request
     * @param User $user
     * @param UserManager $userManager
     * @param AuthService $authService
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          ref=@Model(type=\Model\User\User::class, groups={"User"})
     *      )
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Returns edited user and jwt.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="users")
     */
    public function putAction(Request $request, User $user, UserManager $userManager, AuthService $authService)
    {
        $data = $request->request->all();
        $data['userId'] = $user->getId();
        $user = $userManager->update($data);
        $jwt = $authService->getToken($data['userId']);

        return $this->view(array(
            'user' => $user,
            'jwt' => $jwt,
        ), 200);
    }

    /**
     * Get username available
     *
     * @Get("/users/available/{username}")
     * @param string $username
     * @param UserManager $userManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Username is available.",
     * )
     * @SWG\Response(
     *     response=422,
     *     description="Username is NOT available.",
     * )
     * @SWG\Tag(name="users")
     */
    public function availableAction($username, UserManager $userManager)
    {
        $user = $this->getUser();
        if ($user && $user instanceof User && mb_strtolower($username) === $user->getUsernameCanonical()) {
            return $this->view([]);
        }
        $userManager->validateUsername(0, $username);

        return $this->view([]);
    }

    /**
     * Enable/disable user
     *
     * @Post("/users/enable")
     * @param Request $request
     * @param User $user
     * @param UserManager $userManager
     * @param DeviceManager $deviceManager
     * @throws NotFoundHttpException
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="User enabled/disabled.",
     * )
     * @SWG\Response(
     *     response=404,
     *     description="User not found.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="users")
     */
    public function setEnableAction(Request $request, User $user, UserManager $userManager, DeviceManager $deviceManager)
    {
        $enabled = $request->request->get('enabled');
        $userManager->setEnabled($user->getId(), $enabled);

        if (!$enabled) {
            $allDevices = $deviceManager->getAll($user->getId());
            /** @var Device $device */
            foreach ($allDevices as $device) {
                $deviceManager->delete($device->toArray());
            }
        }

        return $this->view([]);
    }


    /**
     * Get matching with other user
     *
     * @Get("/matching/{userId}", requirements={"userId"="\d+"})
     * @param integer $userId
     * @param MatchingManager $matchingManager
     * @param User $user
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns matching with other user.",
     * )
     * @SWG\Response(
     *     response=400,
     *     description="User does NOT exist.",
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Unknown exception.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="users")
     */
    public function getMatchingAction($userId, MatchingManager $matchingManager, User $user)
    {
        if (null === $userId) {
            return $this->view([], 400);
        }

        try {
            $matching = $matchingManager->getMatchingBetweenTwoUsersBasedOnAnswers($user->getId(), $userId);
        } catch (\Exception $e) {
            return $this->view([], 500);
        }

        return $this->view($matching, 200);
    }

    /**
     * Get similarity with other user
     *
     * @Get("/similarity/{userId}", requirements={"userId"="\d+"})
     * @param integer $userId
     * @param SimilarityManager $similarityManager
     * @param User $user
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns similarity with other user.",
     * )
     * @SWG\Response(
     *     response=400,
     *     description="User does NOT exist.",
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Unknown exception.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="users")
     */
    public function getSimilarityAction($userId, User $user, SimilarityManager $similarityManager)
    {
        if (null === $userId) {
            return $this->view([], 400);
        }

        try {
            $similarity = $similarityManager->getCurrentSimilarity($user->getId(), $userId);
            $result = array('similarity' => $similarity->getSimilarity());

        } catch (\Exception $e) {
            return $this->view([], 500);
        }

        return $this->view($result, 200);
    }

    /**
     * Get paginated user content
     *
     * @Get("/content")
     * @param Request $request
     * @param User $user
     * @param Paginator $paginator
     * @param ContentPaginatedManager $contentPaginatedManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns paginated contents.",
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Unknown exception.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="users")
     */
    public function getUserContentAction(Request $request, User $user, Paginator $paginator, ContentPaginatedManager $contentPaginatedManager)
    {
        $commonWithId = $request->get('commonWithId', null);
        $tag = $request->get('tag', array());
        $type = $request->get('type', array());

        $filters = array('id' => $user->getId());

        if ($commonWithId) {
            $filters['commonWithId'] = (int)$commonWithId;
        }

        foreach ($tag as $singleTag) {
            if (!empty($singleTag)) {
                $filters['tag'][] = urldecode($singleTag);
            }
        }

        foreach ($type as $singleType) {
            if (!empty($singleType)) {
                $filters['type'][] = urldecode($singleType);
            }
        }

        try {
            $result = $paginator->paginate($filters, $contentPaginatedManager, $request);
            $result['totals'] = $contentPaginatedManager->countAll($user->getId());
        } catch (\Exception $e) {

            return $this->view([], 500);
        }

        return $this->view($result, 200);
    }

    /**
     * Get paginated compared user content
     *
     * @Get("/content/compare/{userId}", requirements={"userId"="\d+"})
     * @param integer $userId
     * @param Request $request
     * @param User $user
     * @param Paginator $paginator
     * @param ContentComparePaginatedManager $contentComparePaginatedManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns paginated compared contents.",
     * )
     * @SWG\Response(
     *     response=400,
     *     description="User does NOT exist.",
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Unknown exception.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="users")
     */
    public function getUserContentCompareAction($userId, Request $request, User $user, Paginator $paginator, ContentComparePaginatedManager $contentComparePaginatedManager)
    {
        $tag = $request->get('tag', array());
        $type = $request->get('type', array());
        $showOnlyCommon = $request->get('showOnlyCommon', 0);

        if (null === $userId) {
            return $this->view([], 400);
        }

        $filters = array('id' => $userId, 'id2' => $user->getId(), 'showOnlyCommon' => (int)$showOnlyCommon);

        foreach ($tag as $singleTag) {
            if (!empty($singleTag)) {
                $filters['tag'][] = urldecode($singleTag);
            }
        }

        foreach ($type as $singleType) {
            if (!empty($singleType)) {
                $filters['type'][] = urldecode($singleType);
            }
        }

        try {
            $result = $paginator->paginate($filters, $contentComparePaginatedManager, $request);
            $result['totals'] = $contentComparePaginatedManager->countAll($userId, $user->getId(), $showOnlyCommon);
        } catch (\Exception $e) {

            return $this->view([], 500);
        }

        return $this->view($result, 200);
    }

    /**
     * Get content tags
     *
     * @Get("/content/tags")
     * @param Request $request
     * @param User $user
     * @param ContentTagManager $contentTagManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="search",
     *      in="query",
     *      type="string",
     * )
     * @SWG\Parameter(
     *      name="limit",
     *      in="query",
     *      type="integer",
     *      default=20
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns content tags.",
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Unknown exception.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="users")
     */
    public function getUserContentTagsAction(Request $request, User $user, ContentTagManager $contentTagManager)
    {
        $search = $request->get('search', '');
        $limit = $request->get('limit', 0);

        if ($search) {
            $search = urldecode($search);
        }

        try {
            $result = $contentTagManager->getContentTags($user->getId(), $search, (int)$limit);
        } catch (\Exception $e) {

            return $this->view([], 500);
        }

        return $this->view($result, 200);
    }

    /**
     * Rate content
     *
     * @Post("/content/rate")
     * @param Request $request
     * @param User $user
     * @param RateManager $rateManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          required={"rate", "linkId"},
     *          @SWG\Property(property="rate", type="string", required={"LIKES|DISLIKES|UNRATES|IGNORES"}),
     *          @SWG\Property(property="linkId", type="integer"),
     *          @SWG\Property(property="originContext", type="string"),
     *          @SWG\Property(property="originName", type="string"),
     *          example={ "rate" = "LIKES", "linkId" = 1000, "originContext" = "", "originName" = "" },
     *      )
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Returns rate response.",
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Link NOT found.",
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Unknown exception.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="users")
     */
    public function rateContentAction(Request $request, User $user, RateManager $rateManager)
    {
        $rate = $request->request->get('rate');
        $data = $request->request->all();
        if (isset($data['linkId']) && !isset($data['id'])) {
            $data['id'] = $data['linkId'];
        }

        if (null == $data['linkId'] || null == $rate) {
            return $this->view(array('text' => 'Link Not Found', 'id' => $user->getId(), 'linkId' => $data['linkId']), 400);
        }

        $originContext = isset($data['originContext']) ? $data['originContext'] : null;
        $originName = isset($data['originName']) ? $data['originName'] : null;
        try {
            $result = $rateManager->userRateLink($user->getId(), $data['id'], 'nekuno', null, $rate, true, $originContext, $originName);
        } catch (\Exception $e) {

            return $this->view([], 500);
        }

        return $this->view($result, 201);
    }

    /**
     * Report content
     *
     * @Post("/content/report")
     * @param Request $request
     * @param User $user
     * @param ContentReportManager $contentReportManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          required={"contentId", "reason"},
     *          @SWG\Property(property="contentId", type="integer"),
     *          @SWG\Property(property="reason", type="string"),
     *          @SWG\Property(property="reasonText", type="string"),
     *          example={ "contentId" = 1000, "reason" = "not interesting", "reasonText" = "" },
     *      )
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Returns report response.",
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Unknown exception.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="users")
     */
    public function reportContentAction(Request $request, User $user, ContentReportManager $contentReportManager)
    {
        $reason = $request->request->get('reason');
        $reasonText = $request->request->get('reasonText');
        $contentId = $request->request->get('contentId');

        try {
            $result = $contentReportManager->report($user->getId(), $contentId, $reason, $reasonText);
        } catch (\Exception $e) {

            return $this->view([], 500);
        }

        return $this->view($result, 201);
    }

    /**
     * Get all filters
     *
     * @Get("/filters")
     * @param Request $request
     * @param User $user
     * @param MetadataService $metadataService
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="locale",
     *      in="query",
     *      type="string",
     *      default="es",
     *      required=true
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns all filters.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="users")
     */
    public function getAllFiltersAction(Request $request, User $user, MetadataService $metadataService)
    {
        $locale = $request->query->get('locale', 'es');
        $filters = array();

        $filters['userFilters'] = $metadataService->getUserFilterMetadata($locale, $user->getId());

        $filters['contentFilters'] = $metadataService->getContentFilterMetadata($locale);

        return $this->view($filters, 200);
    }

    /**
     * Get user paginated recommendations
     *
     * @Get("/recommendations/users")
     * @param Request $request
     * @param User $user
     * @param RecommendatorService $recommendatorService
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns user paginated recommendations.",
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Unknown exception.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="users")
     */
    public function getUserRecommendationAction(Request $request, User $user, RecommendatorService $recommendatorService)
    {
        try {
            $result = $recommendatorService->getUserRecommendationFromRequest($request, $user->getId());
        } catch (\Exception $e) {

            return $this->view([], 500);
        }

        return $this->view($result, 200);
    }

    /**
     * Get content paginated recommendations
     *
     * @Get("/recommendations/content")
     * @param Request $request
     * @param User $user
     * @param RecommendatorService $recommendatorService
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns content paginated recommendations.",
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Unknown exception.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="users")
     */
    public function getContentRecommendationAction(Request $request, User $user, RecommendatorService $recommendatorService)
    {
        try {
            $result = $recommendatorService->getContentRecommendationFromRequest($request, $user->getId());
        } catch (\Exception $e) {

            return $this->view([], 500);
        }

        return $this->view($result, 200);
    }

    /**
     * Get content recommendations tags
     *
     * @Get("/recommendations/content/tags")
     * @param Request $request
     * @param ContentRecommendationTagManager $contentRecommendationTagModel
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="search",
     *      in="query",
     *      type="string",
     * )
     * @SWG\Parameter(
     *      name="limit",
     *      in="query",
     *      type="integer",
     *      default=20
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns content recommendations tags.",
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Unknown exception.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="users")
     */
    public function getContentAllTagsAction(Request $request, ContentRecommendationTagManager $contentRecommendationTagModel)
    {
        $search = $request->get('search', '');
        $limit = $request->get('limit', 0);

        if ($search) {
            $search = urldecode($search);
        }

        try {
            $result = $contentRecommendationTagModel->getAllTags($search, $limit);
        } catch (\Exception $e) {

            return $this->view([], 500);
        }

        return $this->view($result, 200);
    }

    /**
     * Get user status
     *
     * @Get("/status")
     * @param User $user
     * @param UserManager $userManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns user status.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="users")
     */
    public function statusAction(User $user, UserManager $userManager)
    {
        $status = $userManager->getStatus($user->getId());

        return $this->view(array('status' => $status), 200);
    }

    /**
     * Get user stats
     *
     * @Get("/stats")
     * @param User $user
     * @param UserStatsService $userStatsService
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns user stats.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="users")
     */
    public function statsAction(User $user, UserStatsService $userStatsService)
    {
        $stats = $userStatsService->getStats($user->getId());

        return $this->view($stats->toArray(), 200);
    }

    /**
     * Get user compared stats
     *
     * @Get("/stats/compare/{userId}")
     * @param integer $userId
     * @param User $user
     * @param UserStatsService $userStatsService
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns user compared stats.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="users")
     */
    public function statsCompareAction($userId, User $user, UserStatsService $userStatsService)
    {
        $stats = $userStatsService->getComparedStats($user->getId(), $userId);

        return $this->view($stats->toArray(), 200);
    }


    /**
     * Get link affinity
     *
     * @Get("/affinity/{linkId}")
     * @param integer $linkId
     * @param User $user
     * @param AffinityManager $affinityManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns link affinity.",
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Link id is null.",
     * )
     * @SWG\Response(
     *     response=500,
     *     description="Unknown exception.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="users")
     */
    public function getAffinityAction($linkId, User $user, AffinityManager $affinityManager)
    {
        if (null === $linkId) {
            return $this->view([], 400);
        }

        try {
            $affinity = $affinityManager->getAffinity($user->getId(), $linkId);
        } catch (\Exception $e) {

            return $this->view([], 500);
        }

        return $this->view($affinity, !empty($affinity) ? 201 : 200);
    }

    /**
     * @param $message
     * @throws ValidationException
     */
    protected function throwRegistrationException($message)
    {
        $errorList = new ErrorList();
        $errorList->addError('registration', $message);
        throw new ValidationException($errorList);
    }

    private function exceptionMessagesToString(\Exception $e)
    {
        $errorMessage = $e->getMessage();
        if ($e instanceof ValidationException) {
            foreach ($e->getErrors() as $errors) {
                if (is_array($errors)) {
                    $errorMessage .= "\n" . implode("\n", $errors);
                } elseif (is_string($errors)) {
                    $errorMessage .= "\n" . $errors . "\n";
                }
            }
        }

        return $errorMessage;
    }
}