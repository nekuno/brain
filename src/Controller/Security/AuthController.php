<?php

namespace Controller\Security;

use FOS\RestBundle\Controller\Annotations\Options;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\FOSRestController;
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
     * @Options("/{url}")
     * @param Request $request
     * @return Response
     * @SWG\Response(
     *     response=200,
     *     description="Returns response with required headers",
     * )
     * @SWG\Tag(name="auth")
     */
    public function preflightAction($url, Request $request)
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
     *          @SWG\Property(property="locale", type="string"),
     *          example={ "resourceOwner" = "", "oauthToken" = "", "refreshToken" = "", "locale" = "en" },
     *      )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns array with jwt, profile and questionsTotal",
     * )
     * @SWG\Tag(name="auth")
     */
    public function loginAction(Request $request, AuthService $authService)
    {
        $resourceOwner = $request->request->get('resourceOwner');
        $oauthToken = $request->request->get('oauthToken');
        $refreshToken = $request->request->get('refreshToken');
        $locale = $request->query->get('locale');

        if ($resourceOwner && $oauthToken) {
            $jwt = $authService->loginByResourceOwner($resourceOwner, $oauthToken, $refreshToken);
        }
        else {
            throw new BadRequestHttpException('Los datos introducidos no coinciden con nuestros registros.');
        }

        $user = $authService->getUser($jwt);

        //$profile = $app['users.profile.model']->getById($user->getId());

        //$questionsFilters = array('id' => $user->getId(), 'locale' => $locale);
        //$countQuestions = $app['users.questions.model']->countTotal($questionsFilters);

        //return $app->json(array('jwt' => $jwt, 'profile' => $profile, 'questionsTotal' => $countQuestions));

        return $this->view(['jwt' => $jwt]);
    }
}