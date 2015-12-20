<?php

namespace Controller\Security;

use Service\AuthService;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class AuthController
{

    public function loginAction(Request $request, Application $app)
    {

        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response();
            $response->headers->set('Access-Control-Allow-Methods', $request->headers->get('Access-Control-Request-Method'));
            $response->headers->set('Access-Control-Allow-Headers', $request->headers->get('Access-Control-Request-Headers'));

            return $response;
        }

        $username = $request->request->get('username');
        $password = $request->request->get('password');

        if (!$username || !$password) {
            throw new BadRequestHttpException('username and password required');
        }

        /* @var $authService AuthService */
        $authService = $app['auth.service'];
        $jwt = $authService->login($username, $password);

        return $app->json(array('jwt' => $jwt));

    }

}