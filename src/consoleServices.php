<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;


$app['chatMessageNotifications.service'] = function (Silex\Application $app) {
    return new \Service\ChatMessageNotifications($app['emailNotification.service'], $app['orm.ems']['mysql_brain'], $app['dbs']['mysql_social'], $app['translator'], $app['users.model'], $app['users.profile.model']);
};

$app['affinityRecalculations.service'] = function (Silex\Application $app) {
    return new \Service\AffinityRecalculations($app['emailNotification.service'], $app['translator'], $app['neo4j.graph_manager'], $app['links.model'], $app['users.model'], $app['users.affinity.model']);
};

$app['migrateSocialInvitations.service'] = function (Silex\Application $app) {
    return new \Service\MigrateSocialInvitations($app['neo4j.graph_manager'], $app['dbs']['mysql_social']);
};

$app['instant.client'] = $app->share(function (Silex\Application $app) {
    return new GuzzleHttp\Client(array('base_url' => $app['instant.host']));
});

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

        $response = array('error' => $e->getMessage());

        if ($app['debug']) {
            $response['debug'] = array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            );
        }

        $headers = $e instanceof HttpExceptionInterface ? $e->getHeaders() : array();

        return $app->json($response, $code, $headers);
    }

);
