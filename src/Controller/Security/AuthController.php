<?php

namespace Controller\Security;

use Service\AuthService;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class AuthController
{

    public function loginAction(Request $request, Application $app)
    {

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