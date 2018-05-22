<?php

namespace Controller\Security;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Options;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\FOSRestController;
use Model\Profile\ProfileManager;
use Model\Question\UserAnswerPaginatedManager;
use Model\User\User;
use Nelmio\ApiDocBundle\Annotation\Security;
use Service\AuthService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Swagger\Annotations as SWG;

class AuthController extends FOSRestController
{
    /**
     * Preflight action. Set required headers.
     *
     * @Options("/{url}", requirements={"url"=".+"})
     * @param Request $request
     * @return Response
     * @SWG\Response(
     *     response=200,
     *     description="Returns response with required headers",
     * )
     * @SWG\Tag(name="auth")
     */
    public function preFlightAction(Request $request)
    {
        $response = new Response();
        $response->headers->set('Access-Control-Allow-Methods', $request->headers->get('Access-Control-Request-Method'));
        $response->headers->set('Access-Control-Allow-Headers', $request->headers->get('Access-Control-Request-Headers'));

        return $response;
    }

    /**
     * Login user
     *
     * @Post("/login")
     * @param Request $request
     * @param AuthService $authService
     * @param ProfileManager $profileManager
     * @param UserAnswerPaginatedManager $userAnswerPaginatedManager
     * @return mixed
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          required={"resourceOwner", "oauthToken"},
     *          @SWG\Property(property="resourceOwner", type="string"),
     *          @SWG\Property(property="oauthToken", type="string"),
     *          @SWG\Property(property="refreshToken", type="string"),
     *          example={ "resourceOwner" = "", "oauthToken" = "", "refreshToken" = "" },
     *      )
     * )
     * @SWG\Parameter(
     *      name="locale",
     *      in="query",
     *      type="string",
     *      default="es",
     *      required=true
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns array with jwt, profile and questionsTotal",
     * )
     * @SWG\Tag(name="auth")
     */
    public function loginAction(Request $request, AuthService $authService, ProfileManager $profileManager, UserAnswerPaginatedManager $userAnswerPaginatedManager)
    {
        $resourceOwner = $request->request->get('resourceOwner');
        $oauthToken = $request->request->get('oauthToken');
        $refreshToken = $request->request->get('refreshToken');
        $locale = $request->query->get('locale', 'es');

        if ($resourceOwner && $oauthToken) {
            $jwt = $authService->loginByResourceOwner($resourceOwner, $oauthToken, $refreshToken);
        }
        else {
            throw new BadRequestHttpException('Los datos introducidos no coinciden con nuestros registros.');
        }

        $user = $authService->getUser($jwt);
        $profile = $profileManager->getById($user->getId());
        $questionsFilters = array('id' => $user->getId(), 'locale' => $locale);
        $countQuestions = $userAnswerPaginatedManager->countTotal($questionsFilters);

        return $this->view(['jwt' => $jwt, 'profile' => $profile, 'questionsTotal' => $countQuestions]);
    }

    /**
     * Auto-login user
     *
     * @Get("/autologin")
     * @param Request $request
     * @param User $user
     * @param ProfileManager $profileManager
     * @param UserAnswerPaginatedManager $userAnswerPaginatedManager
     * @return mixed
     * @SWG\Parameter(
     *      name="locale",
     *      in="query",
     *      type="string",
     *      default="es",
     *      required=true
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns array with user, profile and questionsTotal",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="auth")
     */
    public function autologinAction(Request $request, User $user, ProfileManager $profileManager, UserAnswerPaginatedManager $userAnswerPaginatedManager)
    {
        $profile = $profileManager->getById($user->getId());

        $locale = $request->query->get('locale');
        $questionFilters = array('id' => $user->getId(), 'locale' => $locale);
        $questionsTotal = $userAnswerPaginatedManager->countTotal($questionFilters);

        return $this->view(['user' => $user, 'profile' => $profile, 'questionsTotal' => $questionsTotal]);
    }
}