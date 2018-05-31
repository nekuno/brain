<?php

namespace Controller;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Swagger\Annotations as SWG;

class DefaultController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get welcome message
     *
     * @Get("/")
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns 200.",
     * )
     * @SWG\Tag(name="default")
     */
    public function getStatusAction()
    {
        return $this->view('Welcome to Nekuno Brain!' , 200);
    }
}
