<?php

/**
 * Instant routes
 */

$instant = $app['controllers_factory'];

$instant->get('/users/{id}/contact/from', 'users.relations.controller:contactFromAction');
$instant->get('/users/{id}/contact/to', 'users.relations.controller:contactToAction');
$instant->get('/users/{from}/contact/{to}', 'users.relations.controller:contactAction');

$app->mount('/instant', $instant);