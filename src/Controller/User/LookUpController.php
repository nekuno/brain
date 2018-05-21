<?php

namespace Controller\User;

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\LookUp\LookUpManager;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Swagger\Annotations as SWG;

class LookUpController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get lookup data
     *
     * @Get("/lookUp")
     * @param Request $request
     * @param LookUpManager $lookUpManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="email",
     *      in="query",
     *      type="string"
     * )
     * @SWG\Parameter(
     *      name="twitterUsername",
     *      in="query",
     *      type="string"
     * )
     * @SWG\Parameter(
     *      name="facebookUsername",
     *      in="query",
     *      type="string"
     * )
     * @SWG\Parameter(
     *      name="gender",
     *      in="query",
     *      type="string"
     * )
     * @SWG\Parameter(
     *      name="location",
     *      in="query",
     *      type="string"
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns look up data.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="lookup")
     */
    public function getAction(Request $request, LookUpManager $lookUpManager)
    {
        $userData = array();
        $userData['email'] = $request->query->get('email');
        $userData['twitterUsername'] = $request->query->get('twitterUsername');
        $userData['facebookUsername'] = $request->query->get('facebookUsername');
        $userData['gender'] = $request->query->get('gender');
        $userData['location'] = $request->query->get('location');

        $lookUpData = $lookUpManager->completeUserData($userData);

        return $this->view($lookUpData, 200);
    }

    /**
     * Set lookup data
     *
     * @Post("/lookUp/users/{userId}", requirements={"userId"="\d+"})
     * @param integer $userId
     * @param Request $request
     * @param LookUpManager $lookUpManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          @SWG\Property(property="email", type="string"),
     *          @SWG\Property(property="twitterUsername", type="string"),
     *          @SWG\Property(property="facebookUsername", type="string"),
     *          @SWG\Property(property="gender", type="string"),
     *          @SWG\Property(property="location"),
     *      )
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Returns created look up data.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="lookup")
     */
    public function setAction($userId, Request $request, LookUpManager $lookUpManager)
    {
        $userData = array();
        $userData['email'] = $request->request->get('email');
        $userData['twitterUsername'] = $request->request->get('twitterUsername');
        $userData['facebookUsername'] = $request->request->get('facebookUsername');
        $userData['gender'] = $request->request->get('gender');
        $userData['location'] = $request->request->get('location');

        $lookUpData = $lookUpManager->set($userId, $userData);

        return $this->view($lookUpData, 201);
    }

    /**
     * Set lookup from web hook
     *
     * @Post("/lookUp/webHook", name="setLookUpFromWebHook")
     * @param Request $request
     * @param LookUpManager $lookUpManager
     * @param LoggerInterface $logger
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=201,
     *     description="Returns empty array.",
     * )
     * @Security(name="Bearer")
     * @SWG\Tag(name="lookup")
     */
    public function setFromWebHookAction(Request $request, LookUpManager $lookUpManager, LoggerInterface $logger)
    {
        $logger->info(sprintf('Web hook called with content: %s', $request->getContent()));

        $lookUpManager->setFromWebHook($request);

        return $this->view([], 201);
    }
}