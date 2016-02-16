<?php

use Model\User\RelationsModel;

/* @var $app Silex\Application */
/* @var $controllers \Silex\Controller */
$controllers = $app['controllers'];

/**
 * Only client and social routes
 */

/** User Routes */
$app->match('{url}', 'auth.controller:preflightAction')->assert('url', '.+')->method('OPTIONS');
$app->post('/login', 'auth.controller:loginAction');

$app->get('/users', 'users.controller:indexAction');
$app->post('/users', 'users.controller:postAction');
$app->put('/users/{id}', 'users.controller:putAction');
$app->get('/users/{id}', 'users.controller:getAction');
$app->get('/users/find', 'users.controller:findAction');
$app->get('/users/available/{username}', 'users.controller:availableAction');
$app->post('/users/validate', 'users.controller:validateAction');

$app->get('/users/{id}/profile', 'users.profile.controller:getAction')->value('id', null);
$app->post('/users/{id}/profile', 'users.profile.controller:postAction')->value('id', null);
$app->put('/users/{id}/profile', 'users.profile.controller:putAction')->value('id', null);
$app->delete('/users/{id}/profile', 'users.profile.controller:deleteAction')->value('id', null);
$app->get('/profile/metadata', 'users.profile.controller:getMetadataAction');
$app->get('/profile/filters', 'users.profile.controller:getFiltersAction');
$app->get('/profile/tags/{type}', 'users.profile.controller:getProfileTagsAction');
$app->post('/profile/validate', 'users.profile.controller:validateAction')->value('id', null);

$app->get('/users/{id}/tokens', 'users.tokens.controller:getAllAction')->value('id', null);
$app->get('/users/{id}/tokens/{resourceOwner}', 'users.tokens.controller:getAction')->value('id', null);
$app->post('/users/{id}/tokens/{resourceOwner}', 'users.tokens.controller:postAction')->value('id', null);
$app->put('/users/{id}/tokens/{resourceOwner}', 'users.tokens.controller:putAction')->value('id', null);
$app->delete('/users/{id}/tokens/{resourceOwner}', 'users.tokens.controller:deleteAction')->value('id', null);

$app->get('/users/{id}/privacy', 'users.privacy.controller:getAction')->value('id', null);
$app->post('/users/{id}/privacy', 'users.privacy.controller:postAction')->value('id', null);
$app->put('/users/{id}/privacy', 'users.privacy.controller:putAction')->value('id', null);
$app->delete('/users/{id}/privacy', 'users.privacy.controller:deleteAction')->value('id', null);
$app->get('/privacy/metadata', 'users.privacy.controller:getMetadataAction');
$app->post('/privacy/validate', 'users.privacy.controller:validateAction')->value('id', null);

$app->get('/users/{from}/blocks', 'users.relations.controller:indexAction')->value('relation', RelationsModel::BLOCKS);
$app->get('/users/{from}/blocks/{to}', 'users.relations.controller:getAction')->value('relation', RelationsModel::BLOCKS);
$app->post('/users/{from}/blocks/{to}', 'users.relations.controller:postAction')->value('relation', RelationsModel::BLOCKS);
$app->delete('/users/{from}/blocks/{to}', 'users.relations.controller:deleteAction')->value('relation', RelationsModel::BLOCKS);

$app->get('/users/{from}/favorites', 'users.relations.controller:indexAction')->value('relation', RelationsModel::FAVORITES);
$app->get('/users/{from}/favorites/{to}', 'users.relations.controller:getAction')->value('relation', RelationsModel::FAVORITES);
$app->post('/users/{from}/favorites/{to}', 'users.relations.controller:postAction')->value('relation', RelationsModel::FAVORITES);
$app->delete('/users/{from}/favorites/{to}', 'users.relations.controller:deleteAction')->value('relation', RelationsModel::FAVORITES);

$app->get('/users/{from}/likes', 'users.relations.controller:indexAction')->value('relation', RelationsModel::LIKES);
$app->get('/users/{from}/likes/{to}', 'users.relations.controller:getAction')->value('relation', RelationsModel::LIKES);
$app->post('/users/{from}/likes/{to}', 'users.relations.controller:postAction')->value('relation', RelationsModel::LIKES);
$app->delete('/users/{from}/likes/{to}', 'users.relations.controller:deleteAction')->value('relation', RelationsModel::LIKES);

$app->get('/users/{from}/reports', 'users.relations.controller:indexAction')->value('relation', RelationsModel::REPORTS);
$app->get('/users/{from}/reports/{to}', 'users.relations.controller:getAction')->value('relation', RelationsModel::REPORTS);
$app->post('/users/{from}/reports/{to}', 'users.relations.controller:postAction')->value('relation', RelationsModel::REPORTS);
$app->delete('/users/{from}/reports/{to}', 'users.relations.controller:deleteAction')->value('relation', RelationsModel::REPORTS);

$app->get('/users/{from}/relations/{to}', 'users.relations.controller:getAction');

$app->get('/users/{id}/contact/from', 'users.relations.controller:contactFromAction');
$app->get('/users/{id}/contact/to', 'users.relations.controller:contactToAction');
$app->get('/users/{from}/contact/{to}', 'users.relations.controller:contactAction');

$app->get('/users/{id1}/matching/{id2}', 'users.controller:getMatchingAction');
$app->get('/users/{id1}/similarity/{id2}', 'users.controller:getSimilarityAction');
$app->get('/users/{id}/questions', 'users.controller:getUserQuestionsAction');
// TODO: Refactor in social to use the second compare route instead of the first one
$app->get('/users/{id}/questions/compare/{id2}', 'users.controller:getOldUserQuestionsCompareAction');
$app->get('/users/{id}/questions/compare-new/{id2}', 'users.controller:getUserQuestionsCompareAction');
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

$app->post('/users/{userId}/answers/explain', 'users.answers.controller:explainAction');
$app->get('/users/{userId}/answers', 'users.answers.controller:indexAction');
$app->post('/users/{userId}/answers', 'users.answers.controller:createAction'); // TODO: rename to answerAction
$app->get('/users/{userId}/answers/count', 'users.answers.controller:countAction');
$app->get('/users/{userId}/answers/{questionId}', 'users.answers.controller:getAnswerAction');
$app->delete('/users/{userId}/answers/{questionId}', 'users.answers.controller:deleteAnswerAction');
$app->post('/users/{userId}/answers/{questionId}', 'users.answers.controller:updateAction'); // TODO: Remove this
$app->post('/answers/validate', 'users.answers.controller:validateAction');

$app->get('/users/{userId}/data/status', 'users.data.controller:getStatusAction')->value('resourceOwner', null);

/** Questionnaire routes */
$app->get('/questionnaire/questions', 'questionnaire.questions.controller:getQuestionsAction')->value('limit', 20); //TODO: Remove
$app->get('/questionnaire/questions/next', 'questionnaire.questions.controller:getNextQuestionAction');
$app->get('/questionnaire/questions/register', 'questionnaire.questions.controller:getDivisiveQuestionsAction');
$app->post('/questionnaire/questions', 'questionnaire.questions.controller:postQuestionAction');
$app->post('/questionnaire/questions/validate', 'questionnaire.questions.controller:validateAction');
$app->get('/questionnaire/questions/{id}', 'questionnaire.questions.controller:getQuestionAction');
$app->get('/questionnaire/questions/{id}/stats', 'questionnaire.questions.controller:statsAction');
$app->post('/questionnaire/questions/{id}/skip', 'questionnaire.questions.controller:skipAction');
$app->post('/questionnaire/questions/{id}/report', 'questionnaire.questions.controller:reportAction');

/** Content routes */
$app->post('/add/links', 'fetch.controller:addLinkAction');
$app->get('/fetch/links', 'fetch.controller:fetchLinksAction')->value('userId', null)->value('resource', null);

/** LookUp routes */
$app->get('/lookUp', 'lookUp.controller:getAction');
$app->post('lookUp/users/{id}', 'lookUp.controller:setAction');

$app->post('/lookUp/webHook', 'lookUp.controller:setFromWebHookAction')->bind('setLookUpFromWebHook');

/** Thread routes */
$app->get('/threads/{id}/recommendation', 'users.threads.controller:getRecommendationAction');
$app->put('/threads/{id}', 'users.threads.controller:putAction');
$app->delete('/threads/{id}', 'users.threads.controller:deleteAction');

/** Group routes */
$app->get('/groups/{id}/members', 'users.groups.controller:getMembersAction');
$app->post('/groups/{id}/users/{userId}', 'users.groups.controller:addUserAction');
$app->delete('/groups/{id}/users/{userId}', 'users.groups.controller:removeUserAction');

/** Invitation routes */
// TODO: Beware before refactoring this route that admin uses '/invitations' route
$app->get('/user/{id}/invitations', 'users.invitations.controller:indexByUserAction');
$app->get('/user/{id}/invitations/available', 'users.invitations.controller:getAvailableByUserAction');
$app->post('/user/{id}/invitations/available/{nOfAvailable}', 'users.invitations.controller:setUserAvailableAction');

$app->post('/invitations/token/validate/{token}', 'users.invitations.controller:validateTokenAction');
$app->post('/user/{userId}/invitations/consume/{token}', 'users.invitations.controller:consumeAction');
$app->get('/invitations/count', 'users.invitations.controller:countTotalAction');
$app->get('/invitations/users/{id}/count', 'users.invitations.controller:countByUserAction');
$app->post('/invitations/{id}/users/{userId}/send', 'users.invitations.controller:sendAction');


/**
 * Client, social and admin routes (shared routes)
 */

/** Group routes */
$app->get('/groups/{id}', 'users.groups.controller:getAction');

/** Invitation routes */
$app->get('/invitations/{id}', 'users.invitations.controller:getAction');
$app->post('/invitations', 'users.invitations.controller:postAction');
$app->put('/invitations/{id}', 'users.invitations.controller:putAction');
$app->delete('/invitations/{id}', 'users.invitations.controller:deleteAction');
$app->post('/invitations/validate', 'users.invitations.controller:validateAction');


/**
 * Admin routes
 */

/** Group routes */
$app->get('/groups', 'admin.groups.controller:getAllAction');
$app->post('/groups', 'admin.groups.controller:postAction');
$app->put('/groups/{id}', 'admin.groups.controller:putAction');
$app->delete('/groups/{id}', 'admin.groups.controller:deleteAction');
$app->post('/groups/validate', 'admin.groups.controller:validateAction');

/** Invitation routes */
$app->get('/invitations', 'admin.invitations.controller:indexAction');

/** EnterpriseUser routes */
$app->get('/enterpriseUsers/{id}', 'admin.enterpriseUsers.controller:getAction');
$app->post('/enterpriseUsers', 'admin.enterpriseUsers.controller:postAction');
$app->put('/enterpriseUsers/{id}', 'admin.enterpriseUsers.controller:putAction');
$app->delete('/enterpriseUsers/{id}', 'admin.enterpriseUsers.controller:deleteAction');
$app->post('/enterpriseUsers/{id}', 'admin.enterpriseUsers.controller:validateAction');

/** EnterpriseUser Group routes */
$app->get('/enterpriseUsers/{enterpriseUserId}/groups', 'admin.enterpriseUsers.groups.controller:getAllAction');
$app->get('/enterpriseUsers/{enterpriseUserId}/groups/{id}', 'admin.enterpriseUsers.groups.controller:getAction');
$app->post('/enterpriseUsers/{enterpriseUserId}/groups', 'admin.enterpriseUsers.groups.controller:postAction');
$app->put('/enterpriseUsers/{enterpriseUserId}/groups/{id}', 'admin.enterpriseUsers.groups.controller:putAction');
$app->delete('/enterpriseUsers/{enterpriseUserId}/groups/{id}', 'admin.enterpriseUsers.groups.controller:deleteAction');
$app->post('/enterpriseUsers/{enterpriseUserId}/groups/{id}', 'admin.enterpriseUsers.groups.controller:validateAction');
$app->get('/enterpriseUsers/groups/{id}/communities', 'admin.enterpriseUsers.communities.controller:getByGroupAction');

/** EnterpriseUser Invitation routes */
$app->post('/enterpriseUsers/{enterpriseUserId}/invitations', 'admin.enterpriseUsers.invitations.controller:postAction');
$app->delete('/enterpriseUsers/{enterpriseUserId}/invitations/{id}', 'admin.enterpriseUsers.invitations.controller:deleteAction');
$app->get('/enterpriseUsers/{enterpriseUserId}/invitations/{id}', 'admin.enterpriseUsers.invitations.controller:getAction');
$app->put('/enterpriseUsers/{enterpriseUserId}/invitations/{id}', 'admin.enterpriseUsers.invitations.controller:putAction');
$app->post('/enterpriseUsers/{enterpriseUserId}/invitations/{id}', 'admin.enterpriseUsers.invitations.controller:validateAction');

$controllers
    ->assert('id', '\d+')
    ->convert(
        'id',
        function ($id) {
            return (int)$id;
        }
    )
    ->assert('userId', '\d+')
    ->convert(
        'userId',
        function ($id) {
            return (int)$id;
        }
    )
    ->assert('from', '\d+')
    ->convert(
        'from',
        function ($from) {
            return (int)$from;
        }
    )
    ->assert('to', '\d+')
    ->convert(
        'to',
        function ($to) {
            return (int)$to;
        }
    );
