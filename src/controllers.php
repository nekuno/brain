<?php

use Symfony\Component\HttpFoundation\Request;

//Request::setTrustedProxies(array('127.0.0.1'));

$app['users.controller'] = $app->share(
    function () {

        return new \Controller\User\UserController;
    }
);

$app['users.profile.controller'] = $app->share(
    function () {

        return new \Controller\User\ProfileController();
    }
);

$app['users.data.controller'] = $app->share(
    function () {

        return new \Controller\User\DataController();
    }
);

$app['questionnaire.questions.controller'] = $app->share(
    function () {

        return new Controller\Questionnaire\QuestionController;
    }
);

$app['users.answers.controller'] = $app->share(
    function () {

        return new \Controller\User\AnswerController;
    }
);

$app['fetch.controller'] = $app->share(
    function () {

        return new Controller\FetchController;
    }
);

/**
 * Middleware for filter some request
 */
$app->before(
    function (Request $request) use ($app) {

        // Filter access by IP
        $validClientIP = array(
            '127.0.0.1'
        );

        if (!in_array($ip = $request->getClientIp(), $validClientIP)) {
            return $app->json(array(), 403); // 403 Access forbidden
        }

        // Parse request content and populate parameters
        if ($request->getContentType() === 'application/json' || $request->getContentType() === 'json') {
            $data = json_decode(utf8_encode($request->getContent()), true);
            if (json_last_error()) {
                return $app->json(array('Error parsing JSON data.'), 400);
            }
            $request->request->replace(is_array($data) ? $data : array());
        }
    }
);

/**
 * Error handling
 */
$app->error(
    function (\Exception $e, $code) use ($app) {

        $response = array(
            'error' => array(
                'code' => $code,
                'text' => 'An error ocurred',
            )
        );

        if ($app['debug']) {
            $response = array(
                'error' => array(
                    'code' => $code,
                    'exceptionCode' => $e->getCode(),
                    'text' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTrace(),
                )
            );
        }

        return $app->json($response, $code);
    }

);
