<?php

namespace Controller\Security;

use FOS\RestBundle\Controller\Annotations\Options;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\FOSRestController;
use Service\AuthService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;


class AuthController extends FOSRestController
{
    /**
     * @Options("/{url}")
     * @param Request $request
     * @return Response
     */
    public function preflightAction($url, Request $request)
    {
        $response = new Response();
        $response->headers->set('Access-Control-Allow-Methods', $request->headers->get('Access-Control-Request-Method'));
        $response->headers->set('Access-Control-Allow-Headers', $request->headers->get('Access-Control-Request-Headers'));

        return $response;
    }

    /**
     * @Post("/login")
     * @param Request $request
     * @param AuthService $authService
     * @return mixed
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