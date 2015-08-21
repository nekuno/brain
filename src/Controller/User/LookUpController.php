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
            $fullContactData = $this->lookUpFullContact->get(LookUpFullContact::EMAIL_TYPE, $email);
            $peopleGraphData = $this->lookUpPeopleGraph->get(LookUpFullContact::EMAIL_TYPE, $email);

            $lookUpData = $this->lookUpPeopleGraph->merge($fullContactData, $peopleGraphData);
            $lookUpData->setEmail($email);
            $lookUpData->setLookedUpType(LookUpData::LOOKED_UP_BY_EMAIL);
            $lookUpData->setLookedUpValue($email);
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
        if(! $lookUpData = $this->em->getRepository('\Model\Entity\LookUpData')->findOneBy(array(
            'lookedUpType' => LookUpData::LOOKED_UP_BY_TWITTER_USERNAME,
            'lookedUpValue' => $twitterUsername,
        ))) {

            $twitterBaseUrl = 'https://twitter.com/';

            $fullContactData = $this->lookUpFullContact->get(LookUpFullContact::TWITTER_TYPE, $twitterUsername);
            $peopleGraphData = $this->lookUpPeopleGraph->get(LookUpPeopleGraph::URL_TYPE, $twitterBaseUrl . $twitterUsername);

            $lookUpData = $this->lookUpPeopleGraph->merge($fullContactData, $peopleGraphData);
            $lookUpData->addSocialProfiles(array('twitter' => $twitterBaseUrl . $twitterUsername));
            $lookUpData->setLookedUpType(LookUpData::LOOKED_UP_BY_TWITTER_USERNAME);
            $lookUpData->setLookedUpValue($twitterUsername);
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
        if(! $lookUpData = $this->em->getRepository('\Model\Entity\LookUpData')->findOneBy(array(
            'lookedUpType' => LookUpData::LOOKED_UP_BY_FACEBOOK_USERNAME,
            'lookedUpValue' => $facebookUsername,
        ))) {
            $facebookBaseUrl = 'https://facebook.com/';

            $fullContactData = $this->lookUpFullContact->get(LookUpFullContact::FACEBOOK_TYPE, $facebookUsername);
            $peopleGraphData = $this->lookUpPeopleGraph->get(LookUpPeopleGraph::URL_TYPE, $facebookBaseUrl . $facebookUsername);

            $lookUpData = $this->lookUpPeopleGraph->merge($fullContactData, $peopleGraphData);
            $lookUpData->addSocialProfiles(array('twitter' => $facebookBaseUrl . $facebookUsername));
            $lookUpData->setLookedUpType(LookUpData::LOOKED_UP_BY_FACEBOOK_USERNAME);
            $lookUpData->setLookedUpValue($facebookUsername);
            $this->em->persist($lookUpData);
            $this->em->flush();
        }

        return $app->json($lookUpData->toArray());
    }
}