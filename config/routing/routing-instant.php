<?php

/**
 * Instant routes
 */

$instant = $app['controllers_factory'];

$instant->get('/users/{id}', 'instant.users.controller:getAction');

$instant->get('/users/{id}/contact/from', 'instant.relations.controller:contactFromAction');
$instant->get('/users/{id}/contact/to', 'instant.relations.controller:contactToAction');
$instant->get('/users/{from}/contact/{to}', 'instant.relations.controller:contactAction');

$app->mount('/instant', $instant);

$instant
    ->assert('id', '\d+')
    ->convert(
        'id',
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