<?php

namespace Controller\User;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\User\User;
use Model\User\UserManager;

/**
 * @RouteResource("User")
 */
class UserController extends FOSRestController implements ClassResourceInterface
{
    /**
     * @Get("/users")
     * @param User $user
     * @param UserManager $userManager
     * @return \FOS\RestBundle\View\View
     */
    public function cgetAction(User $user, UserManager $userManager)
    {
        $user = $userManager->getById($user->getId())->jsonSerialize();

        return $this->view($user, 200);
    }

    public function newAction()
    {}

    /**
     * @Get("/users/{slug}")
     * @param string $slug
     * @param UserManager $userManager
     * @param User $user
     * @return \FOS\RestBundle\View\View
     */
    public function getAction($slug, User $user, UserManager $userManager)
    {
        $userArray = $userManager->getBySlug($slug)->jsonSerialize();
        $userArray = $userManager->deleteOtherUserFields($userArray);

        if (empty($userArray)) {
            return $this->view($userArray, 404);
        }

        return $this->view($user, 200);

    }
}