<?php

namespace Controller\Security;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Options;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\FOSRestController;
use Model\User\User;
use Nelmio\ApiDocBundle\Annotation\Security;
use Service\AuthService;
use Service\UserService;
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
     * @param UserService $userService
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
    public function loginAction(Request $request, AuthService $authService, UserService $userService)
    {
        $resourceOwner = $request->request->get('resourceOwner');
        $oauthToken = $request->request->get('oauthToken');
        $refreshToken = $request->request->get('refreshToken');
        $locale = $request->query->get('locale', 'es');
//TODO: Move to AuthService?
        if ($resourceOwner && $oauthToken) {
            $jwt = $authService->loginByResourceOwner($resourceOwner, $oauthToken, $refreshToken);
        }
        else {
            throw new BadRequestHttpException('Los datos introducidos no coinciden con nuestros registros.');
        }

        $data = $userService->getOwnUser($jwt, $locale);

        return $this->view($data);
    }

    /**
     * Auto-login user
     *
     * @Get("/autologin")
     * @param Request $request
     * @param User $user
     * @param UserService $userService
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
    public function autologinAction(Request $request, User $user, UserService $userService)
    {
        $locale = $request->query->get('locale');

        $data = $userService->buildOwnUser($user, $locale);

        return $this->view($data);
    }
}