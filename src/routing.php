<?php

/* @var $app Silex\Application */
/* @var $controllers \Silex\Controller */
$controllers = $app['controllers'];

require __DIR__.'/../config/routing/routing-client.php';
require __DIR__.'/../config/routing/routing-admin.php';
require __DIR__.'/../config/routing/routing-instant.php';

$controllers
    ->assert('id', '\d+')
    ->convert(
        'id',
        function ($id) {
            return (int)$id;
        }
    )
    ->assert('userId', '\d+')
    ->convert(
        'userId',
        function ($id) {
            return (int)$id;
        }
    )
    ->assert('from', '\d+')
    ->convert(
        'from',
        function ($from) {
            return (int)$from;
        }
    )
    ->assert('to', '\d+')
    ->convert(
        'to',
        function ($to) {
            return (int)$to;
        }
    );
