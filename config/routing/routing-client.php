<?php

use Model\User\RelationsModel;

/**
 * Client and social routes
 */

/** User Routes */
$app->match('{url}', 'auth.controller:preflightAction')->assert('url', '.+')->method('OPTIONS');
$app->post('/login', 'auth.controller:loginAction');

$app->get('/users', 'users.controller:getAction');
$app->get('/users/{id}', 'users.controller:getOtherAction');
$app->post('/users', 'users.controller:postAction');
$app->put('/users', 'users.controller:putAction');
$app->post('/users/validate', 'users.controller:validateAction');
$app->get('/users/find', 'users.controller:findAction');
$app->get('/users/available/{username}', 'users.controller:availableAction');

$app->get('/profile', 'users.profile.controller:getAction');
$app->get('/profile/{id}', 'users.profile.controller:getOtherAction')->value('id', null);
$app->post('/profile', 'users.profile.controller:postAction');
$app->put('/profile', 'users.profile.controller:putAction');
$app->post('/profile/validate', 'users.profile.controller:validateAction');
$app->delete('/profile', 'users.profile.controller:deleteAction');
$app->get('/profile/metadata', 'users.profile.controller:getMetadataAction');
$app->get('/profile/filters', 'users.profile.controller:getFiltersAction');
$app->get('/profile/tags/{type}', 'users.profile.controller:getProfileTagsAction');

$app->get('/users/tokens/{id}', 'users.tokens.controller:getAllAction');
$app->get('/tokens/{resourceOwner}', 'users.tokens.controller:getAction');
$app->post('/tokens/{resourceOwner}', 'users.tokens.controller:postAction');
$app->put('/tokens/{resourceOwner}', 'users.tokens.controller:putAction');
$app->delete('/tokens/{resourceOwner}', 'users.tokens.controller:deleteAction');

$app->get('/privacy', 'users.privacy.controller:getAction');
//TODO: This route is only used in social
$app->get('/users/{id}/privacy', 'users.privacy.controller:getOtherAction')->value('id', null);
$app->post('/privacy', 'users.privacy.controller:postAction');
$app->put('/privacy', 'users.privacy.controller:putAction');
$app->delete('/privacy', 'users.privacy.controller:deleteAction');
$app->get('/privacy/metadata', 'users.privacy.controller:getMetadataAction');
$app->post('/privacy/validate', 'users.privacy.controller:validateAction');

$app->get('/blocks', 'users.relations.controller:indexAction')->value('relation', RelationsModel::BLOCKS);
$app->get('/blocks/{to}', 'users.relations.controller:getAction')->value('relation', RelationsModel::BLOCKS);
$app->post('/blocks/{to}', 'users.relations.controller:postAction')->value('relation', RelationsModel::BLOCKS);
$app->delete('/blocks/{to}', 'users.relations.controller:deleteAction')->value('relation', RelationsModel::BLOCKS);

$app->get('/favorites', 'users.relations.controller:indexAction')->value('relation', RelationsModel::FAVORITES);
$app->get('/favorites/{to}', 'users.relations.controller:getAction')->value('relation', RelationsModel::FAVORITES);
$app->post('/favorites/{to}', 'users.relations.controller:postAction')->value('relation', RelationsModel::FAVORITES);
$app->delete('/favorites/{to}', 'users.relations.controller:deleteAction')->value('relation', RelationsModel::FAVORITES);

$app->get('/likes', 'users.relations.controller:indexAction')->value('relation', RelationsModel::LIKES);
$app->get('/likes/{to}', 'users.relations.controller:getAction')->value('relation', RelationsModel::LIKES);
$app->post('/likes/{to}', 'users.relations.controller:postAction')->value('relation', RelationsModel::LIKES);
$app->delete('/likes/{to}', 'users.relations.controller:deleteAction')->value('relation', RelationsModel::LIKES);

$app->get('/reports', 'users.relations.controller:indexAction')->value('relation', RelationsModel::REPORTS);
$app->get('/reports/{to}', 'users.relations.controller:getAction')->value('relation', RelationsModel::REPORTS);
$app->post('/reports/{to}', 'users.relations.controller:postAction')->value('relation', RelationsModel::REPORTS);
$app->delete('/reports/{to}', 'users.relations.controller:deleteAction')->value('relation', RelationsModel::REPORTS);

$app->get('/relations/{to}', 'users.relations.controller:getAction');
$app->get('/other-relations/{from}', 'users.relations.controller:getOtherAction');

$app->get('/users/{id1}/matching/{id2}', 'users.controller:getMatchingAction');
$app->get('/users/{id1}/similarity/{id2}', 'users.controller:getSimilarityAction');
$app->get('/users/{id}/content', 'users.controller:getUserContentAction');
$app->get('/users/{id}/content/compare/{id2}', 'users.controller:getUserContentCompareAction');
$app->get('/users/{id}/content/tags', 'users.controller:getUserContentTagsAction');
$app->post('/users/{id}/content/rate', 'users.controller:rateContentAction');
$app->get('/users/{id}/filters', 'users.controller:getAllFiltersAction');
$app->get('/users/{id}/threads', 'users.threads.controller:getByUserAction');
$app->post('/users/{id}/threads', 'users.threads.controller:postAction');
$app->get('/users/{id}/recommendations/users', 'users.controller:getUserRecommendationAction');
$app->get('/users/{id}/recommendations/content', 'users.controller:getContentRecommendationAction');
$app->get('/users/{id}/recommendations/content/tags', 'users.controller:getContentRecommendationTagsAction');
$app->get('/users/{id}/status', 'users.controller:statusAction');
$app->get('/users/{id}/stats', 'users.controller:statsAction');
$app->get('/users/{id1}/stats/compare/{id2}', 'users.controller:statsCompareAction');

$app->get('/users/{userId}/affinity/{linkId}', 'users.controller:getAffinityAction');

$app->get('/answers', 'users.answers.controller:indexAction');
// TODO: Remove compare-old route when social is gone
$app->get('/answers/compare-old/{id}', 'users.answers.controller:getOldUserAnswersCompareAction');
$app->get('/answers/compare/{id}', 'users.answers.controller:getUserAnswersCompareAction');
$app->post('/answers/explain', 'users.answers.controller:explainAction');
$app->post('/answers', 'users.answers.controller:answerAction'); // TODO: rename to answerAction
$app->get('/users/{userId}/answers/count', 'users.answers.controller:countAction');
$app->get('/answers/{questionId}', 'users.answers.controller:getAnswerAction');
$app->delete('/answers/{questionId}', 'users.answers.controller:deleteAnswerAction');
$app->post('/answers/{questionId}', 'users.answers.controller:updateAction'); // TODO: Remove this
$app->post('/answers/validate', 'users.answers.controller:validateAction');

$app->get('/users/{userId}/data/status', 'users.data.controller:getStatusAction')->value('resourceOwner', null);

/** Questionnaire routes */
$app->get('/questions/next', 'questionnaire.questions.controller:getNextQuestionAction');
$app->get('/questions/register', 'questionnaire.questions.controller:getDivisiveQuestionsAction');
$app->post('/questions', 'questionnaire.questions.controller:postQuestionAction');
$app->post('/questions/validate', 'questionnaire.questions.controller:validateAction');
$app->get('/questions/{id}', 'questionnaire.questions.controller:getQuestionAction');
$app->post('/questions/{id}/skip', 'questionnaire.questions.controller:skipAction');
$app->post('/questions/{id}/report', 'questionnaire.questions.controller:reportAction');

/** Content routes */
$app->post('/add/links', 'fetch.controller:addLinkAction');

/** LookUp routes */
$app->get('/lookUp', 'lookUp.controller:getAction');
$app->post('lookUp/users/{id}', 'lookUp.controller:setAction');

$app->post('/lookUp/webHook', 'lookUp.controller:setFromWebHookAction')->bind('setLookUpFromWebHook');

/** Thread routes */
$app->get('/threads/{id}/recommendation', 'users.threads.controller:getRecommendationAction');
$app->put('/threads/{id}', 'users.threads.controller:putAction');
$app->delete('/threads/{id}', 'users.threads.controller:deleteAction');

/** Group routes */
$app->get('/groups/{id}', 'users.groups.controller:getAction');
$app->get('/groups/{id}/members', 'users.groups.controller:getMembersAction');
$app->post('/groups/{id}/members', 'users.groups.controller:addUserAction');
$app->delete('/groups/{id}/members', 'users.groups.controller:removeUserAction');

/** Invitation routes */
$app->get('/invitations', 'users.invitations.controller:indexByUserAction');
$app->get('/invitations/available', 'users.invitations.controller:getAvailableByUserAction');
$app->post('/invitations/available/{nOfAvailable}', 'users.invitations.controller:setUserAvailableAction');
$app->get('/invitations/{id}', 'users.invitations.controller:getAction');
$app->post('/invitations', 'users.invitations.controller:postAction');
$app->put('/invitations/{id}', 'users.invitations.controller:putAction');
$app->delete('/invitations/{id}', 'users.invitations.controller:deleteAction');
$app->post('/invitations/validate', 'users.invitations.controller:validateAction');
$app->post('/invitations/token/validate/{token}', 'users.invitations.controller:validateTokenAction');
$app->post('/invitations/consume/{token}', 'users.invitations.controller:consumeAction');
$app->get('/invitations/count', 'users.invitations.controller:countByUserAction');
$app->post('/invitations/{id}/send', 'users.invitations.controller:sendAction');

/**
 * Client routes
 */
$app->get('/client/version', 'client.controller:versionAction');
