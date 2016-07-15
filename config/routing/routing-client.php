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

$app->get('/privacy', 'users.privacy.controller:getAction');
$app->post('/privacy', 'users.privacy.controller:postAction');
$app->put('/privacy', 'users.privacy.controller:putAction');
$app->delete('/privacy', 'users.privacy.controller:deleteAction');
$app->get('/privacy/metadata', 'users.privacy.controller:getMetadataAction');
$app->post('/privacy/validate', 'users.privacy.controller:validateAction');

/** Relations routes */
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

$app->get('/matching/{id}', 'users.controller:getMatchingAction');
$app->get('/similarity/{id}', 'users.controller:getSimilarityAction');
$app->get('content', 'users.controller:getUserContentAction');
$app->get('/content/compare/{id}', 'users.controller:getUserContentCompareAction');
$app->get('/content/tags', 'users.controller:getUserContentTagsAction');
$app->post('/content/rate', 'users.controller:rateContentAction');
$app->get('/filters', 'users.controller:getAllFiltersAction');
$app->get('/threads', 'users.threads.controller:getByUserAction');
$app->post('/threads', 'users.threads.controller:postAction');
$app->get('/recommendations/users', 'users.controller:getUserRecommendationAction');
$app->get('/recommendations/content', 'users.controller:getContentRecommendationAction');
$app->get('/recommendations/content/tags', 'users.controller:getContentAllTagsAction');
$app->get('/status', 'users.controller:statusAction');
$app->get('/stats', 'users.controller:statsAction');
$app->get('/stats/compare/{id}', 'users.controller:statsCompareAction');

$app->get('/affinity/{linkId}', 'users.controller:getAffinityAction');

$app->get('/answers', 'users.answers.controller:indexAction');
$app->get('/answers/compare/{id}', 'users.answers.controller:getUserAnswersCompareAction');
$app->post('/answers/explain', 'users.answers.controller:explainAction');
$app->post('/answers', 'users.answers.controller:answerAction');
$app->get('/users/{userId}/answers/count', 'users.answers.controller:countAction');
$app->get('/answers/{questionId}', 'users.answers.controller:getAnswerAction');
$app->delete('/answers/{questionId}', 'users.answers.controller:deleteAnswerAction');
$app->post('/answers/validate', 'users.answers.controller:validateAction');

$app->get('/data/status', 'users.data.controller:getStatusAction')->value('resourceOwner', null);

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
$app->post('/threads/default', 'users.threads.controller:createDefaultAction');
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
 * Tokens routes
 */

$app->post('/tokens/{resourceOwner}', 'users.tokens.controller:postAction');

/**
 * Client routes
 */
$app->get('/client/version', 'client.controller:versionAction');
$app->get('/client/blog-feed', 'client.controller:getBlogFeedAction');

/** Photo routes */
$app->get('/photos', 'users.photos.controller:getAllAction');
$app->get('/photos/{id}', 'users.photos.controller:getAction');
$app->post('/photos', 'users.photos.controller:postAction');
$app->post('/photos/{id}/profile', 'users.photos.controller:postProfileAction');
$app->delete('/photos/{id}', 'users.photos.controller:deleteAction');