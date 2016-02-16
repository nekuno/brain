<?php

namespace Controller\Admin\EnterpriseUser;

use Model\EnterpriseUser\CommunityModel;
use Model\EnterpriseUser\EnterpriseUserModel;
use Silex\Application;

/**
 * Class CommunityController
 * @package Controller
 */
class CommunityController
{
    /**
     * @var \Model\EnterpriseUser\EnterpriseUserModel
     */
    protected $eum;
    /**
     * @var \Model\EnterpriseUser\CommunityModel
     */
    protected $cm;

    public function __construct(EnterpriseUserModel $eum, CommunityModel $cm)
    {
        $this->cm = $cm;
    }

    public function getByGroupAction(Application $app, $id)
    {
        $communities = $this->cm->getByGroup($id);

        return $app->json($communities);

    }

}