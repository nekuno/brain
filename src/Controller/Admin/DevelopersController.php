<?php

namespace Controller\Admin;

use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Service\DeviceService;
use Swagger\Annotations as SWG;

/**
 * This controller is for testing proposes
 *
 * @Route("/admin")
 */
class DevelopersController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Send testing push notification
     *
     * @Post("/notifications/push/{id}", requirements={"id"="\d+"})
     * @param integer $id
     * @param DeviceService $deviceService
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns sent push notification.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function pushNotificationAction($id, DeviceService $deviceService)
    {
        $result = $deviceService->pushMessage(array(
            'title' => 'Testing',
            'body' => 'This is a testing push notification',
            'image' => '/img/no-img/big.jpg',
            'on_click_path' => '/social-networks',
        ), $id);

        return $this->view($result, 200);
    }
}