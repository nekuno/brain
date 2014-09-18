<?php

// User routes
$app->get('/users', 'users.controller:indexAction');
$app->post('/users', 'users.controller:addAction');

$app->get('/users/{id}', 'users.controller:showAction')->value('id', null);
$app->delete('/users/{id}', 'users.controller:deleteAction')->value('id', null);

$app->get('/users/{id1}/matching/{id2}', 'users.controller:getMatchingAction')
    ->value('type', 'answers');

$app->get('/users/{id}/questions', 'users.controller:getUserQuestionsAction');

$app->get('/users/{id}/content', 'users.controller:getUserContentAction');

$app->get('/users/{id}/content/tags', 'users.controller:getUserContentTagsAction');

$app->get('/users/{id}/recommendations/users', 'users.controller:getUserRecommendationAction')
    ->value('type', 'answers');

$app->get('/users/{id}/recommendations/content', 'users.controller:getContentRecommendationAction');
$app->get('/users/{id}/recommendations/content/tags', 'users.controller:getContentRecommendationTagsAction');

// Question routes
$app->post('/questions/answers', 'questions.controller:answerAction');
$app->post('/questions', 'questions.controller:addAction');

// Content routes
$app->post('/add/links', 'fetch.controller:addLinkAction');
$app->get('/fetch/links', 'fetch.controller:fetchLinksAction')
    ->value('userId', null)
    ->value('resource', null);

// Status controller
$app->get('/users/{userId}/data/status', 'users.data.controller:getStatusAction')
    ->value('resourceOwner', null);
