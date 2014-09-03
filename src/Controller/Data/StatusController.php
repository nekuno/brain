<?php


namespace Controller\Data;

use Doctrine\ORM\EntityManager;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class StatusController
{

    public function getStatusAction(Request $request, Application $app)
    {

        

        return $app->json(array(), 200);
    }

}
