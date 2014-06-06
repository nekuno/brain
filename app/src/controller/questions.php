<?php

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

$app->post(
    '/questions',
    function(Request $request) use ($app) {
        $app['monolog']->addDebug('Perfect ___________________------------>>>>>>><<');
        return $app->json(array(), 200);
    }
);