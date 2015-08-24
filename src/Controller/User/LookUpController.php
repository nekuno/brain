<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Controller\User;

use Doctrine\ORM\EntityManager;
use Model\Entity\LookUpData;
use Service\LookUp\LookUpFullContact;
use Service\LookUp\LookUpPeopleGraph;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class LookUpController
 * @package Controller
 */
class LookUpController
{
    /**
     * @var LookUpFullContact
     */
    protected $lookUpFullContact;

    /**
     * @var LookUpPeopleGraph
     */
    protected $lookUpPeopleGraph;

    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct(LookUpFullContact $lookUpFullContact, LookUpPeopleGraph $lookUpPeopleGraph, EntityManager $em)
    {
        $this->lookUpFullContact = $lookUpFullContact;
        $this->lookUpPeopleGraph = $lookUpPeopleGraph;
        $this->em = $em;
    }

    /**
     * @param Application $app
     * @param string $email
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getByEmailAction(Application $app, $email)
    {
        if(! $lookUpData = $this->em->getRepository('\Model\Entity\LookUpData')->findOneBy(array(
            'lookedUpType' => LookUpData::LOOKED_UP_BY_EMAIL,
            'lookedUpValue' => $email,
        ))) {
            $lookUpData = new LookUpData();
            $lookUpData->setEmail($email);
            $lookUpData->setLookedUpType(LookUpData::LOOKED_UP_BY_EMAIL);
            $lookUpData->setLookedUpValue($email);
            $this->em->persist($lookUpData);
            $this->em->flush();
        }
        $socialProfiles = $lookUpData->getSocialProfiles();
        if(empty($socialProfiles)) {
            $fullContactData = $this->lookUpFullContact->get(LookUpFullContact::EMAIL_TYPE, $email, $lookUpData->getId());
            $peopleGraphData = $this->lookUpPeopleGraph->get(LookUpPeopleGraph::EMAIL_TYPE, $email, $lookUpData->getId());
            $lookUpData = $this->lookUpFullContact->merge($lookUpData, $fullContactData);
            $lookUpData = $this->lookUpPeopleGraph->merge($lookUpData, $peopleGraphData);

            $this->em->persist($lookUpData);
            $this->em->flush();
        }

        return $app->json($lookUpData->toArray());
    }

    /**
     * @param Application $app
     * @param string $twitterUsername
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getByTwitterUsernameAction(Application $app, $twitterUsername)
    {
        $twitterBaseUrl = 'https://twitter.com/';

        if(! $lookUpData = $this->em->getRepository('\Model\Entity\LookUpData')->findOneBy(array(
            'lookedUpType' => LookUpData::LOOKED_UP_BY_TWITTER_USERNAME,
            'lookedUpValue' => $twitterUsername,
        ))) {

            $lookUpData = new LookUpData();
            $lookUpData->setLookedUpType(LookUpData::LOOKED_UP_BY_TWITTER_USERNAME);
            $lookUpData->setLookedUpValue($twitterUsername);
            $this->em->persist($lookUpData);
            $this->em->flush();
        }
        $socialProfiles = $lookUpData->getSocialProfiles();
        if(empty($socialProfiles)) {
            $fullContactData = $this->lookUpFullContact->get(LookUpFullContact::TWITTER_TYPE, $twitterUsername, $lookUpData->getId());
            $peopleGraphData = $this->lookUpPeopleGraph->get(LookUpPeopleGraph::URL_TYPE, $twitterBaseUrl . $twitterUsername, $lookUpData->getId());

            $lookUpData = $this->lookUpFullContact->merge($lookUpData, $fullContactData);
            $lookUpData = $this->lookUpPeopleGraph->merge($lookUpData, $peopleGraphData);

            $this->em->persist($lookUpData);
            $this->em->flush();
        }

        return $app->json($lookUpData->toArray());
    }

    /**
     * @param Application $app
     * @param string $facebookUsername
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getByFacebookUsernameAction(Application $app, $facebookUsername)
    {
        $facebookBaseUrl = 'https://facebook.com/';

        if(! $lookUpData = $this->em->getRepository('\Model\Entity\LookUpData')->findOneBy(array(
            'lookedUpType' => LookUpData::LOOKED_UP_BY_FACEBOOK_USERNAME,
            'lookedUpValue' => $facebookUsername,
        ))) {
            $lookUpData = new LookUpData();
            $lookUpData->setLookedUpType(LookUpData::LOOKED_UP_BY_FACEBOOK_USERNAME);
            $lookUpData->setLookedUpValue($facebookUsername);
            $this->em->persist($lookUpData);
            $this->em->flush();
        }
        $socialProfiles = $lookUpData->getSocialProfiles();
        if(empty($socialProfiles)) {
            $fullContactData = $this->lookUpFullContact->get(LookUpFullContact::FACEBOOK_TYPE, $facebookUsername, 2);
            $peopleGraphData = $this->lookUpPeopleGraph->get(LookUpPeopleGraph::URL_TYPE, $facebookBaseUrl . $facebookUsername, 2);

            $lookUpData = $this->lookUpFullContact->merge($lookUpData, $fullContactData);
            $lookUpData = $this->lookUpPeopleGraph->merge($lookUpData, $peopleGraphData);

            $this->em->persist($lookUpData);
            $this->em->flush();
        }

        return $app->json($lookUpData->toArray());
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
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
}