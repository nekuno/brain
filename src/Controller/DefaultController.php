<?php

namespace Controller;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Jean85\PrettyVersions;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get welcome message
     *
     * @Get("/")
     * @return Response
     * @SWG\Response(
     *     response=200,
     *     description="Returns 200.",
     * )
     * @SWG\Tag(name="default")
     */
    public function getWelcomeAction()
    {
        $version = PrettyVersions::getVersion('nekuno/brain');

        $view = $this->renderView('default/welcome.html.twig' , array('version' => $version));

        return new Response($view, 200, ['Content-Type' => 'text/html']);
    }
}
