<?php

// User routes
$app->get('/users/matching', 'users.controller:getMatchingAction')->value('id1', null)->value('id2', null);
$app->get('/users/{id}', 'users.controller:showAction')->value('id', null);
$app->delete('/users/{id}', 'users.controller:deleteAction')->value('id', null);
$app->get('/users', 'users.controller:indexAction');
$app->post('/users', 'users.controller:addAction');

// Question routes
$app->post('/questions', 'questions.controller:addAction');

// Answer routes
$app->post('/answers', 'answers.controller:addAction');

// Matching routes

