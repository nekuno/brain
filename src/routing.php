<?php

// User routes
$app->get('/users', 'users.controller:indexAction');
$app->post('/users', 'users.controller:addAction');
$app->get('/users/matching', 'users.controller:getMatchingByQuestionsAction')->value('id1', null)->value('id2', null);
$app->get('/users/matching/by-questions', 'users.controller:getMatchingByQuestionsAction')->value('id1', null)->value('id2', null);
$app->get('/users/matching/by-content', 'users.controller:getMatchingByContentAction')->value('id1', null)->value('id2', null);
$app->get('/users/{id}', 'users.controller:showAction')->value('id', null);
$app->delete('/users/{id}', 'users.controller:deleteAction')->value('id', null);

// Question routes
$app->post('/questions/answers', 'questions.controller:answerAction');
$app->post('/questions', 'questions.controller:addAction');

// Content routes
$app->post('/social/links', 'social.controller:addLink');
$app->get('/social/{resource}/{userId}/fetch/links', 'social.controller:fetchLinksAction');
