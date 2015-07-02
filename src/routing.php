<?php

/* @var $app Silex\Application */
/* @var $controllers \Silex\Controller */
$controllers = $app['controllers'];

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
$app->get('/users/{id}/filters', 'users.controller:getAllFiltersAction');
$app->get('/users/{id}/recommendations/users', 'users.controller:getUserRecommendationAction');
$app->get('/users/{id}/recommendations/content', 'users.controller:getContentRecommendationAction');
$app->get('/users/{id}/recommendations/content/tags', 'users.controller:getContentRecommendationTagsAction');
$app->get('/users/{id}/status', 'users.controller:statusAction');
$app->get('/users/{id}/stats', 'users.controller:statsAction');
$app->get('/users/{id1}/stats/compare/{id2}', 'users.controller:statsCompareAction');

$app->get('/users/{userId}/affinity/{linkId}', 'users.controller:getAffinityAction');

$app->post('/users/{userId}/answers/explain', 'users.answers.controller:explainAction');
$app->get('/users/{userId}/answers', 'users.answers.controller:indexAction');
$app->post('/users/{userId}/answers', 'users.answers.controller:createAction'); // TODO: rename to answerAction
$app->get('/users/{userId}/answers/count', 'users.answers.controller:countAction');
$app->get('/users/{userId}/answers/{questionId}', 'users.answers.controller:getAnswerAction');
$app->post('/users/{userId}/answers/{questionId}', 'users.answers.controller:updateAction'); // TODO: Remove this
$app->post('/answers/validate', 'users.answers.controller:validateAction');

$app->get('/users/{userId}/data/status', 'users.data.controller:getStatusAction')->value('resourceOwner', null);

/**
 * Questionnaire routes
 */
$app->get('/questionnaire/questions', 'questionnaire.questions.controller:getQuestionsAction')->value('limit', 20);
$app->get('/questionnaire/questions/next', 'questionnaire.questions.controller:getNextQuestionAction');
$app->post('/questionnaire/questions', 'questionnaire.questions.controller:postQuestionAction');
$app->post('/questionnaire/questions/validate', 'questionnaire.questions.controller:validateAction');
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
$app->get('/groups', 'users.groups.controller:getAllAction');
$app->get('/groups/{id}', 'users.groups.controller:getAction');
$app->post('/groups', 'users.groups.controller:postAction');
$app->put('/groups/{id}', 'users.groups.controller:putAction');
$app->delete('/groups/{id}', 'users.groups.controller:deleteAction');
$app->post('/groups/validate', 'users.groups.controller:validateAction');
$app->post('/groups/{id}/users/{userId}', 'users.groups.controller:addUserAction');
$app->delete('/groups/{id}/users/{userId}', 'users.groups.controller:removeUserAction');

/**
 * Invitation routes
 */
$app->get('/invitations', 'users.invitations.controller:indexAction');
$app->get('/user/{id}/invitations', 'users.invitations.controller:indexByUserAction');
$app->get('/user/{id}/invitations/available', 'users.invitations.controller:getAvailableByUserAction');
$app->post('/user/{id}/invitations/available/{nOfAvailable}', 'users.invitations.controller:setUserAvailableAction');
$app->get('/invitations/{id}', 'users.invitations.controller:getAction');
$app->post('/invitations', 'users.invitations.controller:postAction');
$app->put('/invitations/{id}', 'users.invitations.controller:putAction');
$app->delete('/invitations/{id}', 'users.invitations.controller:deleteAction');
$app->delete('/invitations', 'users.invitations.controller:deleteAllAction');
$app->post('/invitations/validate', 'users.invitations.controller:validateAction');
$app->post('/invitations/token/validate/{token}', 'users.invitations.controller:validateTokenAction');
$app->post('/user/{userId}/invitations/consume/{token}', 'users.invitations.controller:consumeAction');
$app->get('/invitations/count', 'users.invitations.controller:countTotalAction');
$app->get('/invitations/users/{id}/count', 'users.invitations.controller:countByUserAction');
$app->post('/invitations/{id}/users/{userId}/send', 'users.invitations.controller:sendAction');


$controllers
    ->assert('id', '\d+')
    ->convert(
        'id',
        function ($id) {
            return (integer)$id;
        }
    )
    ->assert('userId', '\d+')
    ->convert(
        'userId',
        function ($id) {
            return (integer)$id;
        }
    );
