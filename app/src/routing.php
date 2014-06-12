<?php

// User routes
$app->get('/users/{id}', 'users.controller:showAction')->value('id', null);
$app->post('/users', 'users.controller:addAction');
$app->post('/users/{id}', 'users.controller:updateAction')->value('id', null);
$app->delete('/users/{id}', 'users.controller:deleteAction')->value('id', null);

// Question routes
$app->get('/questions/{id}', 'questions.controller:showAction')->value('id', null);
$app->post('/questions', 'questions.controller:addAction');
$app->post('/questions/{id}', 'questions.controller:updateAction')->value('id', null);
$app->delete('/questions/{id}', 'questions.controller:deleteAction')->value('id', null);

// Answer routes
$app->get('/answers/{id}', 'answers.controller:showAction')->value('id', null);
$app->post('/answers', 'answers.controller:addAction');
$app->post('/answers/{id}', 'answers.controller:updateAction')->value('id', null);
$app->delete('/answers/{id}', 'answers.controller:deleteAction')->value('id', null);