<?php

namespace Controller\Instant;

use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Service\DeviceService;
use Symfony\Component\HttpFoundation\Request;
use Swagger\Annotations as SWG;

/**
 * @Route("/instant")
 */
class PushNotificationsController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Notify via push notification
     *
     * @Post("/notify")
     * @param Request $request
     * @param DeviceService $deviceService
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          @SWG\Property(property="userId", type="integer"),
     *          @SWG\Property(property="category", type="string"),
     *          @SWG\Property(property="data", type="string[]"),
     *      )
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Returns sent notification.",
     * )
     * @SWG\Tag(name="instant")
     */
    public function notifyAction(Request $request, DeviceService $deviceService)
    {
        $userId = $request->get('userId');
        $category = $request->get('category');
        $data = $request->get('data');

        $result = $deviceService->pushMessage($data, $userId, $category);

        return $this->view($result, 201);
    }
}
