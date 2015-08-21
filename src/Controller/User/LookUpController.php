<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Controller\User;

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

    public function __construct(LookUpFullContact $lookUpFullContact, LookUpPeopleGraph $lookUpPeopleGraph)
    {
        $this->lookUpFullContact = $lookUpFullContact;
        $this->lookUpPeopleGraph = $lookUpPeopleGraph;
    }

    /**
     * @param Application $app
     * @param string $email
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function getByEmailAction(Application $app, $email)
    {
        $fullContactData = $this->lookUpFullContact->get(LookUpFullContact::EMAIL_TYPE, $email);
        $peopleGraphData = $this->lookUpPeopleGraph->get(LookUpFullContact::EMAIL_TYPE, $email);

        return $app->json($fullContactData + $peopleGraphData);
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

        $fullContactData = $this->lookUpFullContact->get(LookUpFullContact::TWITTER_TYPE, $twitterUsername);
        $peopleGraphData = $this->lookUpPeopleGraph->get(LookUpPeopleGraph::URL_TYPE, $twitterBaseUrl . $twitterUsername);

        return $app->json($fullContactData + $peopleGraphData);
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

        $fullContactData = $this->lookUpFullContact->get(LookUpFullContact::FACEBOOK_TYPE, $facebookUsername);
        $peopleGraphData = $this->lookUpPeopleGraph->get(LookUpPeopleGraph::URL_TYPE, $facebookBaseUrl . $facebookUsername);

        return $app->json($fullContactData + $peopleGraphData);
    }
}