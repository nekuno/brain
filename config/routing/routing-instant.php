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