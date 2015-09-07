<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Controller\User;

use Silex\Application;
use Model\User\LookUpModel;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

/**
 * Class LookUpController
 * @package Controller
 */
class LookUpController
{
    /**
     * @var LookUpModel
     */
    protected $lookUpModel;

    public function __construct(LookUpModel $lookUpModel)
    {
        $this->lookUpModel = $lookUpModel;
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getAction(Application $app, Request $request)
    {
        $userData = array();
        $userData['email'] = $request->query->get('email');
        $userData['twitterUsername'] = $request->query->get('twitterUsername');
        $userData['facebookUsername'] = $request->query->get('facebookUsername');
        $userData['gender'] = $request->query->get('gender');
        $userData['location'] = $request->query->get('location');

        $lookUpData = $this->lookUpModel->completeUserData($userData);

        return $app->json($lookUpData);
    }

    /**
     * @param Application $app
     * @param integer $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */

    public function setAction(Application $app, Request $request, $id)
    {
        $userData = array();
        $userData['email'] = $request->request->get('email');
        $userData['twitterUsername'] = $request->request->get('twitterUsername');
        $userData['facebookUsername'] = $request->request->get('facebookUsername');
        $userData['gender'] = $request->request->get('gender');
        $userData['location'] = $request->request->get('location');

        $lookUpData = $this->lookUpModel->set($id, $userData);

        return $app->json($lookUpData);
    }

    public function setFromWebHookAction(Request $request, Application $app)
    {
        /* @var $logger LoggerInterface */
        $logger = $app['monolog'];
        $logger->info(sprintf('Web hook called with content: %s', $request->getContent()));

        $this->lookUpModel->setFromWebHook($request);

        return true;
    }
}