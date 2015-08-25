<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Controller\User;

use Silex\Application;
use Model\User\LookUpModel;

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


    /* TODO: Disable web hook by now (refactor needed)
    public function setFromWebHookAction(Request $request, Application $app)
    {
        $id = $request->get('webHookId');
        if($lookUpData = $this->em->getRepository('\Model\Entity\LookUpData')->findOneBy(array(
            'id' => (int)$id,
        ))) {
            $lookUpData = $this->lookUpFullContact->mergeFromWebHook($lookUpData, $request->getContent());
            $lookUpData = $this->lookUpPeopleGraph->mergeFromWebHook($lookUpData, $request->getContent());
            $this->em->persist($lookUpData);
            $this->em->flush();
        }

        return true;
    }
    */
}