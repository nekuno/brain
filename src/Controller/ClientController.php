<?php

namespace Controller;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use GuzzleHttp\Client;
use Swagger\Annotations as SWG;

class ClientController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get blog feed
     *
     * @Get("/client/blog-feed")
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns Nekuno blog feed.",
     * )
     * @SWG\Tag(name="client")
     */
    public function getBlogFeedAction()
    {
        $client = new Client(array('base_uri' => 'http://blog.nekuno.com/'));
        $blogFeed = $client->get('feed');

        return $this->view($blogFeed->getBody()->getContents(), 200);
    }

    /**
     * Get net status
     *
     * @Get("/client/status")
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns 200.",
     * )
     * @SWG\Tag(name="client")
     */
    public function getStatusAction()
    {
        return $this->view([], 200);
    }
}
