<?php

namespace Controller\Instant;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\User\UserManager;
use Swagger\Annotations as SWG;

/**
 * @Route("/instant")
 */
class UserController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get user
     *
     * @Get("/users/{id}", requirements={"id"="\d+"})
     * @param integer $id
     * @param UserManager $userManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns user.",
     * )
     * @SWG\Response(
     *     response=404,
     *     description="User NOT found.",
     * )
     * @SWG\Tag(name="instant")
     */
    public function getAction($id, UserManager $userManager)
    {
        $userArray = $userManager->getById($id)->jsonSerialize();
        $userArray = $userManager->deleteOtherUserFields($userArray);

        if (empty($userArray)) {

            return $this->view([], 404);
        }

        return $this->view($userArray, 200);
    }

}
