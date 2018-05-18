<?php

namespace Controller\User;

use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\Device\DeviceManager;
use Model\User\User;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Swagger\Annotations as SWG;

class DeviceController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Subscribe device for notifications
     *
     * @Post("/notifications/subscribe")
     * @param User $user
     * @param Request $request
     * @param DeviceManager $deviceManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          @SWG\Property(property="registrationId", type="string"),
     *          @SWG\Property(property="token", type="string"),
     *          @SWG\Property(property="platform", type="string"),
     *      )
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Returns subscribed device.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="devices")
     */
    public function subscribeAction(User $user, Request $request, DeviceManager $deviceManager)
    {
        $data = array(
            'userId' => $user->getId(),
            'registrationId' => $request->get('registrationId'),
            'token' => $request->get('token'),
            'platform' => $request->get('platform'),
        );

        if ($deviceManager->exists($data['registrationId'])) {
            $device = $deviceManager->update($data);
        } else {
            $device = $deviceManager->create($data);
        }

        return $this->view($device->toArray(), 201);
    }

    /**
     * Un-subscribe device
     *
     * @Post("/notifications/unsubscribe")
     * @param User $user
     * @param Request $request
     * @param DeviceManager $deviceManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          @SWG\Property(property="registrationId", type="string"),
     *          @SWG\Property(property="token", type="string"),
     *          @SWG\Property(property="platform", type="string"),
     *      )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns un-subscribed device.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="devices")
     */
    public function unSubscribeAction(User $user, Request $request, DeviceManager $deviceManager)
    {
        $data = array(
            'userId' => $user->getId(),
            'registrationId' => $request->get('registrationId'),
            'token' => $request->get('token'),
            'platform' => $request->get('platform'),
        );

        $device = $deviceManager->delete($data);

        return $this->view($device->toArray(), 200);
    }
}
