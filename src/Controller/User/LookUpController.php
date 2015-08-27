<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Controller\User;

use Silex\Application;
use Model\User\LookUpModel;
use Symfony\Component\HttpFoundation\Request;

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
     * @param string $email
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getByEmailAction(Application $app, $email)
    {
        $lookUpData = $this->lookUpModel->getByEmail($email);
        return $app->json($lookUpData);
    }

    /**
     * @param Application $app
     * @param string $twitterUsername
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getByTwitterUsernameAction(Application $app, $twitterUsername)
    {
        $lookUpData = $this->lookUpModel->getByTwitterUsername($twitterUsername);
        return $app->json($lookUpData);
    }

    /**
     * @param Application $app
     * @param string $facebookUsername
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getByFacebookUsernameAction(Application $app, $facebookUsername)
    {
        $lookUpData = $this->lookUpModel->getByFacebookUsername($facebookUsername);
        return $app->json($lookUpData);
    }

    /**
     * @param Application $app
     * @param integer $id
     * @param string $email
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */

    public function setByEmailAction(Application $app, $id, $email)
    {
        $lookUpData = $this->lookUpModel->setByEmail($id, $email);
        return $app->json($lookUpData);
    }

    /**
     * @param Application $app
     * @param integer $id
     * @param string $twitterUsername
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function setByTwitterUsernameAction(Application $app, $id, $twitterUsername)
    {
        $lookUpData = $this->lookUpModel->setByTwitterUsername($id, $twitterUsername);
        return $app->json($lookUpData);
    }

    /**
     * @param Application $app
     * @param integer $id
     * @param string $facebookUsername
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function setByFacebookUsernameAction(Application $app, $id, $facebookUsername)
    {
        $lookUpData = $this->lookUpModel->setByFacebookUsername($id, $facebookUsername);
        return $app->json($lookUpData);
    }


    public function setFromWebHookAction(Request $request, Application $app)
    {
        $this->lookUpModel->setFromWebHook($request);

        return true;
    }
}