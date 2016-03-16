<?php

/**
 * Social routes
 */

$social = $app['controllers_factory'];

$social->get('/users/find', 'social.users.controller:findAction');
$social->put('/users/{id}', 'social.users.controller:putAction');
$social->get('/users/jwt/{id}', 'social.users.controller:jwtAction');

$social->get('/profile/{id}', 'social.profile.controller:getAction')->value('id', null);
$social->post('/profile/{id}', 'social.profile.controller:postAction');
$social->put('/profile/{id}', 'social.profile.controller:putAction');

$social->get('/tokens/{id}', 'social.tokens.controller:getAllAction');
$social->get('/users/{id}/tokens/{resourceOwner}', 'social.tokens.controller:getAction');
$social->post('/users/{id}/tokens/{resourceOwner}', 'social.tokens.controller:postAction');
$social->put('/users/{id}/tokens/{resourceOwner}', 'social.tokens.controller:putAction');
$social->delete('/users/{id}/tokens/{resourceOwner}', 'social.tokens.controller:deleteAction');

$social->get('/users/{id}/privacy', 'social.privacy.controller:getAction')->value('id', null);
$social->get('/privacy/metadata', 'users.privacy.controller:getMetadataAction');

$social->get('/users/{id}/answers/compare/{id2}', 'social.answers.controller:getUserAnswersCompareAction');
$social->post('/users/{id}/answers/{questionId}', 'social.answers.controller:updateAction');

$social->get('/lookUp', 'social.lookUp.controller:getAction');
$social->post('lookUp/users/{id}', 'social.lookUp.controller:setAction');

$social->get('/groups/{id}', 'social.groups.controller:getAction');

$app->mount('/social', $social);

$social
    ->assert('id', '\d+')
    ->convert(
        'id',
        function ($id) {
            return (int)$id;
        }
    )
    ->assert('id2', '\d+')
    ->convert(
        'id2',
        function ($id) {
            return (int)$id;
        }
    );