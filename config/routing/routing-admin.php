<?php

/**
 * Admin routes
 */

$admin = $app['controllers_factory'];

/** Group routes */
$admin->get('/groups', 'admin.groups.controller:getAllAction');
$admin->get('/groups/{id}', 'admin.groups.controller:getAction');
$admin->post('/groups', 'admin.groups.controller:postAction');
$admin->put('/groups/{id}', 'admin.groups.controller:putAction');
$admin->delete('/groups/{id}', 'admin.groups.controller:deleteAction');
$admin->post('/groups/validate', 'admin.groups.controller:validateAction');

/** Invitation routes */
$admin->get('/invitations', 'admin.invitations.controller:indexAction');
$admin->get('/invitations/{id}', 'admin.invitations.controller:getAction');
$admin->post('/invitations', 'admin.invitations.controller:postAction');
$admin->put('/invitations/{id}', 'admin.invitations.controller:putAction');
$admin->delete('/invitations/{id}', 'admin.invitations.controller:deleteAction');
$admin->post('/invitations/validate', 'admin.invitations.controller:validateAction');

/** EnterpriseUser routes */
$admin->get('/enterpriseUsers/{id}', 'admin.enterpriseUsers.controller:getAction');
$admin->post('/enterpriseUsers', 'admin.enterpriseUsers.controller:postAction');
$admin->put('/enterpriseUsers/{id}', 'admin.enterpriseUsers.controller:putAction');
$admin->delete('/enterpriseUsers/{id}', 'admin.enterpriseUsers.controller:deleteAction');
$admin->post('/enterpriseUsers/{id}', 'admin.enterpriseUsers.controller:validateAction');

/** EnterpriseUser Group routes */
$admin->get('/enterpriseUsers/{enterpriseUserId}/groups', 'admin.enterpriseUsers.groups.controller:getAllAction');
$admin->get('/enterpriseUsers/{enterpriseUserId}/groups/{id}', 'admin.enterpriseUsers.groups.controller:getAction');
$admin->post('/enterpriseUsers/{enterpriseUserId}/groups', 'admin.enterpriseUsers.groups.controller:postAction');
$admin->put('/enterpriseUsers/{enterpriseUserId}/groups/{id}', 'admin.enterpriseUsers.groups.controller:putAction');
$admin->delete('/enterpriseUsers/{enterpriseUserId}/groups/{id}', 'admin.enterpriseUsers.groups.controller:deleteAction');
$admin->post('/enterpriseUsers/{enterpriseUserId}/groups/{id}', 'admin.enterpriseUsers.groups.controller:validateAction');
$admin->get('/enterpriseUsers/groups/{id}/communities', 'admin.enterpriseUsers.communities.controller:getByGroupAction');

/** EnterpriseUser Invitation routes */
$admin->post('/enterpriseUsers/{enterpriseUserId}/invitations', 'admin.enterpriseUsers.invitations.controller:postAction');
$admin->delete('/enterpriseUsers/{enterpriseUserId}/invitations/{id}', 'admin.enterpriseUsers.invitations.controller:deleteAction');
$admin->get('/enterpriseUsers/{enterpriseUserId}/invitations/{id}', 'admin.enterpriseUsers.invitations.controller:getAction');
$admin->put('/enterpriseUsers/{enterpriseUserId}/invitations/{id}', 'admin.enterpriseUsers.invitations.controller:putAction');
$admin->post('/enterpriseUsers/{enterpriseUserId}/invitations/{id}', 'admin.enterpriseUsers.invitations.controller:validateAction');

$app->mount('/admin', $admin);