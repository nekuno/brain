<?php

namespace Controller\Admin\EnterpriseUser;

use Silex\Application;

/**
 * Class CommunityController
 * @package Controller
 */
class CommunityController
{
    public function getByGroupAction(Application $app, $id)
    {
        $communities = $app['enterpriseUsers.communities.model']->getByGroup($id);

        return $app->json($communities);
    }
}