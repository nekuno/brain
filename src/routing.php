<?php

/**
 * Users routes
 */
$app->get('/users', 'users.controller:indexAction');
$app->post('/users', 'users.controller:addAction');

$app->get('/users/{id}', 'users.controller:showAction')->value('id', null);
$app->delete('/users/{id}', 'users.controller:deleteAction')->value('id', null);

$app->get('/users/{id1}/matching/{id2}', 'users.controller:getMatchingAction')
    ->value('type', 'answers');

$app->get('/users/{id}/questions', 'users.controller:getUserQuestionsAction');
$app->get('/users/{id}/questions/compare/{id2}', 'users.controller:getUserQuestionsCompareAction');

$app->get('/users/{id}/content', 'users.controller:getUserContentAction');
$app->get('/users/{id}/content/compare/{id2}', 'users.controller:getUserContentCompareAction');
$app->get('/users/{id}/content/tags', 'users.controller:getUserContentTagsAction');

$app->post('/users/{id}/content/rate', 'users.controller:rateContentAction');

$app->get('/users/{id}/recommendations/users', 'users.controller:getUserRecommendationAction')
    ->value('type', 'answers');

$app->get('/users/{id}/recommendations/content', 'users.controller:getContentRecommendationAction');
$app->get('/users/{id}/recommendations/content/tags', 'users.controller:getContentRecommendationTagsAction');

$app->get('/users/{id}/status', 'users.controller:statusAction');

/**
 * Questionnaire routes
 */
$app->get('/questionnaire/questions/next', 'questionnaire.questions.controller:nextAction');
$app->get('/questionnaire/questions', 'questionnaire.questions.controller:getAnsweredAction');
$app->post('/questionnaire/questions', 'questionnaire.questions.controller:createAction');
$app->post('/questionnaire/questions/{id}', 'questionnaire.questions.controller:updateAction');
$app->post('/questionnaire/questions/{id}/skip', 'questionnaire.questions.controller:skipAction');
$app->post('/questionnaire/questions/{id}/report', 'questionnaire.questions.controller:reportAction');

$app->get('/users/{userId}/answers/{answerId}', 'users.answers.controller:getAction');
$app->post('/users/{userId}/answers', 'users.answers.controller:createAction');
$app->post('/users/{userId}/answers/{answerId}', 'users.answers.controller:updateAction');
$app->post('/users/{userId}/answers/{answerId}/explain', 'users.answers.controller:explainAction');

/**
 * Content routes
 */
$app->post('/add/links', 'fetch.controller:addLinkAction');
$app->get('/fetch/links', 'fetch.controller:fetchLinksAction')
    ->value('userId', null)
    ->value('resource', null);

// Status controller
$app->get('/users/{userId}/data/status', 'users.data.controller:getStatusAction')
    ->value('resourceOwner', null);
