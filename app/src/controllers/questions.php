<?php

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

$app->get(
    '/questions',
    function(Request $request) use ($app) {
        return $app->json(array());
    }
);