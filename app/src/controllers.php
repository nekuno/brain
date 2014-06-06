<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

//Request::setTrustedProxies(array('127.0.0.1'));

include_once 'controllers/admin.php';
include_once 'controllers/users.php';
//include_once 'controllers/questions.php';

$app->before(
    function(Request $request) use ($app) {

        if($request->getContentType() === 'json') {
            // Parse request content and populate parameters
            $data = json_decode(utf8_encode($request->getContent()), true);
            $request->request->replace(is_array($data) ? $data : array());
        }

    }
);

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
