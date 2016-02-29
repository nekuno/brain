<?php

use Symfony\Component\HttpFoundation\RequestMatcher;

/* @var $app Silex\Application */
/* @var $controllers \Silex\Controller */
$controllers = $app['controllers'];

/**
 * Firewall
 */
$app['security.firewalls'] = array(
    'login' => array(
        'pattern' => '^/login$',
        'anonymous' => true,
    ),
    'preFlight' => array(
        'pattern' => new RequestMatcher('^.*$', null, 'OPTIONS'),
        'anonymous' => true,
    ),
    'public_get' => array(
        'pattern' => new RequestMatcher('(^/profile/metadata$)|(^/users/available/)|(^/client/version$)|(^/lookup)', null, 'GET'),
        'anonymous' => true,
    ),
    'public_post' => array(
        'pattern' => new RequestMatcher('(^/users$)|(^/invitations/token/validate/)|(^/lookUp/webHook$)|(^/users/validate$)|(^/profile/validate$)', null, array('POST')),
        'anonymous' => true,
    ),
    'instant' => array(
        'pattern' => new RequestMatcher('^/instant/', null, null, $app['valid_ips']),
        'anonymous' => true,
    ),
    'admin' => array(
        'pattern' => new RequestMatcher('^/admin/', null, null, $app['valid_ips']),
        'anonymous' => true,
    ),
    'social' => array(
        'pattern' => new RequestMatcher('^/social', null, null, $app['valid_ips']),
        'anonymous' => true,
    ),
    'secured' => array(
        'pattern' => '^.*$',
        'users' => $app['security.users_provider'],
        'jwt' => array(
            'use_forward' => true,
            'require_previous_session' => false,
            'stateless' => true,
        )
    ),
);

$app['security.access_rules'] = array(
    array(new RequestMatcher('^/instant', null, null, $app['valid_ips']), 'IS_AUTHENTICATED_ANONYMOUSLY'),
    array(new RequestMatcher('^/instant'), 'ROLE_NO_ACCESS'),
    array(new RequestMatcher('^/admin', null, null, $app['valid_ips']), 'IS_AUTHENTICATED_ANONYMOUSLY'),
    array(new RequestMatcher('^/admin'), 'ROLE_NO_ACCESS'),
    array(new RequestMatcher('^/social', null, null, $app['valid_ips']), 'IS_AUTHENTICATED_ANONYMOUSLY'),
    array(new RequestMatcher('^/social'), 'ROLE_NO_ACCESS'),
);

require __DIR__ . '/../config/routing/routing-client.php';
require __DIR__ . '/../config/routing/routing-social.php';
require __DIR__ . '/../config/routing/routing-admin.php';
require __DIR__ . '/../config/routing/routing-instant.php';

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
