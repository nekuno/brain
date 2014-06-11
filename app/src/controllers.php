<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

//Request::setTrustedProxies(array('127.0.0.1'));

include_once 'controller/users.php';
include_once 'controller/questions.php';

/**
 * Middleware for filter some request
 */
$app->before(
    function(Request $request) use ($app) {

        // Filter access by IP
        $validClientIP = array(
            '127.0.0.1'
        );

        if(!in_array($ip = $request->getClientIp(), $validClientIP)){
            return new \Symfony\Component\HttpFoundation\JsonResponse(array(), 403); // 403 Access forbidden
        }

        // Parse request content and populate parameters
        if($request->getContentType() === 'json') {
            $data = json_decode(utf8_encode($request->getContent()), true);
            $request->request->replace(is_array($data) ? $data : array());
        }

    }
);

/**
 * Error handling
 */
$app->error(
    function (\Exception $e, $code) use ($app) {
        if ($app['debug']) {
            return;
        }

        // 404.html, or 40x.html, or 4xx.html, or error.html
        $templates = array(
            'errors/' . $code . '.html',
            'errors/' . substr($code, 0, 2) . 'x.html',
            'errors/' . substr($code, 0, 1) . 'xx.html',
            'errors/default.html',
        );

        return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
    }

);
