<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Model\Exception\ValidationException;
use Model\Neo4j\Neo4jException;

//Request::setTrustedProxies(array('127.0.0.1'));

/** @var $app Silex\Application */

$app['auth.controller'] = $app->share(
    function () {

        return new \Controller\Security\AuthController;
    }
);

$app['users.controller'] = $app->share(
    function () use ($app) {

        return new \Controller\User\UserController($app['user']);
    }
);

$app['users.tokens.controller'] = $app->share(
    function () use ($app) {

        return new \Controller\User\TokensController($app['user']);
    }
);

$app['users.profile.controller'] = $app->share(
    function () use ($app) {

        return new \Controller\User\ProfileController($app['user']);
    }
);

$app['users.privacy.controller'] = $app->share(
    function () use ($app) {

        return new \Controller\User\PrivacyController($app['user']);
    }
);

$app['users.data.controller'] = $app->share(
    function () use ($app) {

        return new \Controller\User\DataController($app['user']);
    }
);

$app['questionnaire.questions.controller'] = $app->share(
    function () use ($app) {

        return new Controller\Questionnaire\QuestionController($app['user']);
    }
);

$app['users.answers.controller'] = $app->share(
    function () use ($app) {

        return new \Controller\User\AnswerController($app['user'], $app['users.answers.model']);
    }
);

$app['users.groups.controller'] = $app->share(
    function () use ($app) {

        return new \Controller\User\GroupController($app['user'], $app['users.groups.model']);
    }
);

$app['users.threads.controller'] = $app->share(
    function () use ($app) {

        return new \Controller\User\ThreadController($app['user'], $app['users.threads.manager']);
    }
);

$app['users.invitations.controller'] = $app->share(
    function () use ($app) {

        return new \Controller\User\InvitationController($app['user']);
    }
);

$app['users.relations.controller'] = $app->share(
    function () use ($app) {

        return new \Controller\User\RelationsController($app['user']);
    }
);

$app['client.controller'] = $app->share(
    function() {
        return new Controller\ClientController();
    }
);

$app['fetch.controller'] = $app->share(
    function () {

        return new Controller\FetchController;
    }
);

$app['lookUp.controller'] = $app->share(
    function () use ($app) {

        return new \Controller\User\LookUpController($app['users.lookup.model']);
    }
);

$app['admin.groups.controller'] = $app->share(
    function () use ($app) {

        return new \Controller\Admin\GroupController($app['users.groups.model']);
    }
);

$app['admin.invitations.controller'] = $app->share(
    function () use ($app) {

        return new \Controller\Admin\InvitationController;
    }
);

$app['admin.enterpriseUsers.controller'] = $app->share(
    function () use ($app) {

        return new \Controller\Admin\EnterpriseUser\EnterpriseUserController($app['enterpriseUsers.model']);
    }
);

$app['admin.enterpriseUsers.groups.controller'] = $app->share(
    function () use ($app) {

        return new \Controller\Admin\EnterpriseUser\GroupController($app['users.groups.model'], $app['enterpriseUsers.model']);
    }
);

$app['admin.enterpriseUsers.communities.controller'] = $app->share(
    function () use ($app) {

        return new \Controller\Admin\EnterpriseUser\CommunityController($app['enterpriseUsers.model'], $app['enterpriseUsers.communities.model']);
    }
);

$app['admin.enterpriseUsers.invitations.controller'] = $app->share(
    function () use ($app) {

        return new \Controller\Admin\EnterpriseUser\InvitationController($app['users.invitations.model'], $app['enterpriseUsers.model']);
    }
);


/**
 * Middleware for filter some request
 */
$app->before(
    function (Request $request) use ($app) {
        // Parse request content and populate parameters
        if ($request->getContentType() === 'application/json' || $request->getContentType() === 'json') {
            $data = json_decode(utf8_encode($request->getContent()), true);
            if (json_last_error()) {
                return $app->json(array('Error parsing JSON data.'), 400);
            }
            $request->request->replace(is_array($data) ? $data : array());
        }

        return false;
    }
);

$app->after(
    function (Request $request, Response $response) {
        $response->headers->set('Access-Control-Allow-Origin', '*');
    }
);

/**
 * Error handling
 */
$app->error(
    function (\Exception $e, $code) use ($app) {

        $response = array('error' => $e->getMessage());

        $headers = $e instanceof HttpExceptionInterface ? $e->getHeaders() : array();

        if ($e instanceof ValidationException) {
            $response['validationErrors'] = $e->getErrors();
        }

        if ($e instanceof Neo4jException) {
            $response['error'] = isset($e->getData()['message']) ? $e->getData()['message'] : $e->getData() ? $e->getData() : $e->getMessage();
            $response['query'] = $e->getQuery();
            $response['headers'] = $e->getHeaders();
            $response['data'] = $e->getData();
        }

        if ($app['debug']) {
            $response['debug'] = array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            );
        }

        return $app->json($response, $code, $headers);
    }

);
