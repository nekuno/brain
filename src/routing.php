<?php

/**
 * Users routes
 */
$app->get('/users', 'users.controller:indexAction');
$app->post('/users', 'users.controller:addAction');
$app->get('/users/{id}', 'users.controller:showAction')->value('id', null);
$app->delete('/users/{id}', 'users.controller:deleteAction')->value('id', null);

$app->get('/users/{id}/profile', 'users.profile.controller:getAction')->value('id', null);
$app->post('/users/{id}/profile', 'users.profile.controller:postAction')->value('id', null);
$app->put('/users/{id}/profile', 'users.profile.controller:putAction')->value('id', null);
$app->delete('/users/{id}/profile', 'users.profile.controller:deleteAction')->value('id', null);
$app->get('/profile/metadata', 'users.profile.controller:getMetadataAction');
$app->get('/profile/filters', 'users.profile.controller:getFiltersAction');
$app->get('/profile/tags/{type}', 'users.profile.controller:getProfileTagsAction');
$app->post('/profile/validate', 'users.profile.controller:validateAction')->value('id', null);

$app->get('/users/{id1}/matching/{id2}', 'users.controller:getMatchingAction');
$app->get('/users/{id1}/similarity/{id2}', 'users.controller:getSimilarityAction');
$app->get('/users/{id}/questions', 'users.controller:getUserQuestionsAction');
$app->get('/users/{id}/questions/compare/{id2}', 'users.controller:getUserQuestionsCompareAction');
$app->get('/users/{id}/content', 'users.controller:getUserContentAction');
$app->get('/users/{id}/content/compare/{id2}', 'users.controller:getUserContentCompareAction');
$app->get('/users/{id}/content/tags', 'users.controller:getUserContentTagsAction');
$app->post('/users/{id}/content/rate', 'users.controller:rateContentAction');
$app->get('/users/{id}/recommendations/users', 'users.controller:getUserRecommendationAction');
$app->get('/users/{id}/recommendations/content', 'users.controller:getContentRecommendationAction');
$app->get('/users/{id}/recommendations/content/tags', 'users.controller:getContentRecommendationTagsAction');
$app->get('/users/{id}/status', 'users.controller:statusAction');
$app->get('/users/{id}/stats', 'users.controller:statsAction');

$app->get('/users/{userId}/affinity/{linkId}', 'users.controller:getAffinityAction');

$app->post('/users/{userId}/answers/explain', 'users.answers.controller:explainAction');
$app->get('/users/{userId}/answers', 'users.answers.controller:indexAction');
$app->post('/users/{userId}/answers', 'users.answers.controller:createAction'); // TODO: rename to answerAction
$app->get('/users/{userId}/answers/count', 'users.answers.controller:countAction');
$app->get('/users/{userId}/answers/{questionId}', 'users.answers.controller:getAnswerAction');
$app->post('/users/{userId}/answers/{questionId}', 'users.answers.controller:updateAction'); // TODO: Remove this

$app->get('/users/{userId}/data/status', 'users.data.controller:getStatusAction')->value('resourceOwner', null);

/**
 * Questionnaire routes
 */
$app->get('/questionnaire/questions', 'questionnaire.questions.controller:getQuestionsAction')->value('limit', 20);
$app->get('/questionnaire/questions/next', 'questionnaire.questions.controller:getNextQuestionAction');
$app->post('/questionnaire/questions', 'questionnaire.questions.controller:postQuestionAction');
$app->get('/questionnaire/questions/{id}', 'questionnaire.questions.controller:getQuestionAction');
$app->get('/questionnaire/questions/{id}/stats', 'questionnaire.questions.controller:statsAction');
$app->post('/questionnaire/questions/{id}/skip', 'questionnaire.questions.controller:skipAction');
$app->post('/questionnaire/questions/{id}/report', 'questionnaire.questions.controller:reportAction');

/**
 * Content routes
 */
$app->post('/add/links', 'fetch.controller:addLinkAction');
$app->get('/fetch/links', 'fetch.controller:fetchLinksAction')->value('userId', null)->value('resource', null);

/**
 * Group routes
 */

$app->post('/groups', 'users.groups.controller:addAction');
$app->get('/groups/{groupName}', 'users.groups.controller:showAction');
$app->delete('/groups/{groupName}', 'users.groups.controller:deleteAction');
$app->post('/groups/{groupName}/links', 'users.groups.controller:addUserAction');
$app->delete('/groups/{groupName}/links/{id}', 'users.groups.controller:removeUserAction');
