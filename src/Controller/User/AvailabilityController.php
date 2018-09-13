<?php

namespace Controller\User;

use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Service\AvailabilityService;
use Model\User\User;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\Security;
use Swagger\Annotations as SWG;

class AvailabilityController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Create user availability
     *
     * @Post("/availability")
     * @param User $user
     * @param Request $request
     * @param AvailabilityService $availabilityService
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          @SWG\Property(property="availability", type="array"),
     *      )
     * )
     * @SWG\Parameter(
     *      name="locale",
     *      in="query",
     *      type="string",
     *      default="es"
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Returns created availability",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="availability")
     */
    public function postAction(Request $request, User $user, AvailabilityService $availabilityService)
    {
        $data = $request->request->all();

        $availability = $availabilityService->create($data, $user);

        return $this->view($availability, 201);
    }

    /**
     * Get user availability
     *
     * @Get("/availability")
     * @param User $user
     * @param AvailabilityService $availabilityService
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns user availability.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="availability")
     */
    public function getAction(User $user, AvailabilityService $availabilityService)
    {
        $availability = $availabilityService->getByUser($user);

        return $this->view($availability, 200);
    }

    /**
     * Edit own availability
     *
     * @Put("/availability")
     * @param Request $request
     * @param User $user
     * @param AvailabilityService $availabilityService
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          @SWG\Property(property="availability", type="array"),
     *      )
     * )
     * @SWG\Parameter(
     *      name="locale",
     *      in="query",
     *      type="string",
     *      default="es"
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Returns edited availability.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="availability")
     */
    public function putAction(Request $request, User $user, AvailabilityService $availabilityService)
    {
        $data = $request->request->all();

        $availability = $availabilityService->update($data, $user);

        return $this->view($availability);
    }

    /**
     * Delete availability
     *
     * @Delete("/availability")
     * @param User $user
     * @param AvailabilityService $availabilityService
     * @return \FOS\RestBundle\View\View
     *
     * @SWG\Parameter(
     *      name="locale",
     *      in="query",
     *      type="string",
     *      default="es"
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Returns empty",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="proposals")
     */
    public function deleteAction(User $user, AvailabilityService $availabilityService)
    {
        $deleted = $availabilityService->delete($user);

        return $this->view($deleted);
    }

}