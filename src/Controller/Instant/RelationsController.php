<?php

namespace Controller\Instant;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\Contact\ContactManager;
use Swagger\Annotations as SWG;

/**
 * @Route("/instant")
 */
class RelationsController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get users who have been messaged by user
     *
     * @Get("/users/{id}/contact/from", requirements={"id"="\d+"})
     * @param integer $id
     * @param ContactManager $contactManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns users who user has contacted to.",
     * )
     * @SWG\Tag(name="instant")
     */
    public function contactFromAction($id, ContactManager $contactManager)
    {
        $users = $contactManager->contactFrom($id);

        return $this->view($users, 200);
    }

    /**
     * Get users who have messaged to user
     *
     * @Get("/users/{id}/contact/to", requirements={"id"="\d+"})
     * @param integer $id
     * @param ContactManager $contactManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns users who have contacted to user.",
     * )
     * @SWG\Tag(name="instant")
     */
    public function contactToAction($id, ContactManager $contactManager)
    {

        $users = $contactManager->contactTo($id);

        return $this->view($users, 200);
    }

    /**
     * Check if user can contact other user
     *
     * @Get("/users/{from}/contact/{to}", requirements={"from"="\d+", "to"="\d+"})
     * @param integer $from
     * @param integer $to
     * @param ContactManager $contactManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="User can contact.",
     * )
     * @SWG\Response(
     *     response=404,
     *     description="User CANNOT contact.",
     * )
     * @SWG\Tag(name="instant")
     */
    public function contactAction($from, $to, ContactManager $contactManager)
    {
        $contact = $contactManager->canContact($from, $to);

        return $this->view([], $contact ? 200 : 404);
    }
}
