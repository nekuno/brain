<?php

namespace Controller\User;

use Model\User\CommunityModel;
use Silex\Application;

/**
 * Class CommunityController
 * @package Controller
 */
class CommunityController
{

    /**
     * @var CommunityModel
     */
    protected $cm;

    public function __construct(CommunityModel $cm)
    {
        $this->cm = $cm;
    }

    public function getByGroupAction(Application $app, $id)
    {

        $communities = $this->cm->getByGroup($id);

        return $app->json($communities);

    }

}