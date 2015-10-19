<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Model\User;

use Doctrine\ORM\EntityManager;
use Everyman\Neo4j\Query\Row;
use Event\LookUpSocialNetworksEvent;
use Model\Neo4j\GraphManager;
use Model\Entity\LookUpData;
use Service\LookUp\LookUp;
use Service\LookUp\LookUpFullContact;
use Service\LookUp\LookUpPeopleGraph;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class LookUpModel
 *
 * @package Model
 */
class LookUpModel
{
    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var LookUpFullContact
     */
    protected $fullContact;

    /**
     * @var LookUpPeopleGraph
     */
    protected $peopleGraph;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    //neo4j labels => resourceOwner names
    protected $resourceOwners = array(
        'TwitterSocialNetwork' => TokensModel::TWITTER,
        'GoogleplusSocialNetwork' => TokensModel::GOOGLE,
        'YoutubeSocialNetwork' => TokensModel::GOOGLE,
    );
    const LABEL_SOCIAL_NETWORK = 'SocialNetwork';

    /**
     * @var TokensModel
     */
    protected $tm;


    public function __construct(GraphManager $gm, EntityManager $em, TokensModel $tm, LookUpFullContact $fullContact, LookUpPeopleGraph $peopleGraph, EventDispatcher $dispatcher)

    {
        $this->gm = $gm;
        $this->em = $em;
        $this->tm = $tm;
        $this->fullContact = $fullContact;
        $this->peopleGraph = $peopleGraph;
        $this->dispatcher = $dispatcher;
    }

    public function completeUserData($userData, OutputInterface $outputInterface = null)
    {

        $searchedByEmail = false;
        $searchedByTwitterUsername = false;
        $searchedByFacebookUsername = false;

        $lookUpData = $this->initializeLookUpData($userData);

        for($i = 0; $i < 2; $i++) {
            if(! $searchedByEmail && ! $this->isCompleted($lookUpData) && $this->isEmailSet($userData)) {
                $searchedByEmail = true;
                $this->showOutputMessageIfDefined($outputInterface, 'Searching by email...');

                $lookUpData = $this->merge($lookUpData, $this->getByEmail($userData['email']));
                $userData = $this->completeLookUpTypes($userData, $lookUpData);
            }
            if(! $searchedByTwitterUsername && ! $this->isCompleted($lookUpData) && $this->isTwitterUsernameSetInUserData($userData)) {
                $searchedByTwitterUsername = true;
                $this->showOutputMessageIfDefined($outputInterface, 'Searching by twitter username...');

                $lookUpData = $this->merge($lookUpData, $this->getByTwitterUsername($userData['twitterUsername']));
                $userData = $this->completeLookUpTypes($userData, $lookUpData);
            }
            if(! $searchedByFacebookUsername && ! $this->isCompleted($lookUpData) && $this->isFacebookUsernameSetInUserData($userData)) {
                $searchedByFacebookUsername = true;
                $this->showOutputMessageIfDefined($outputInterface, 'Searching by facebook username...');

                $lookUpData = $this->merge($lookUpData, $this->getByFacebookUsername($userData['facebookUsername']));
                $userData = $this->completeLookUpTypes($userData, $lookUpData);
            }
        }

        return $lookUpData;
    }

    public function set($id, $userData, OutputInterface $outputInterface = null)
    {
        $lookUpData = $this->completeUserData($userData, $outputInterface);

        if(isset($lookUpData['socialProfiles']) && ! empty($lookUpData['socialProfiles'])) {
            $this->showOutputMessageIfDefined($outputInterface, 'Adding social profiles to user ' . $id . '...');

            $this->setSocialProfiles($lookUpData['socialProfiles'], $id);

            $this->dispatchSocialNetworksAddedEvent($id, $lookUpData['socialProfiles']);

            return $lookUpData['socialProfiles'];
        }

        return array();
    }

    public function setFromWebHook(Request $request)
    {
        $hash = $request->get('webHookId');
        if($lookUpData = $this->em->getRepository('\Model\Entity\LookUpData')->findOneBy(array('hash' => $hash))) {
            $service = $this->getServiceFromApiResource($lookUpData->getApiResource());
            if($service instanceof LookUp) {
                $lookUpData->setResponse($service->getProcessedResponse($request->request->all()));
                if($lookUpData->getResponse()) {
                    $this->em->persist($lookUpData);
                    $this->em->flush();
                }
            }
        }
    }

    /**
     * @param $userId
     * @param string $resource
     * @param bool $all
     * @return array
     * @throws \Model\Neo4j\Neo4jException
     */
    public function getSocialProfiles($userId, $resource = null, $all = false)
    {
        if (!$userId || !is_int($userId)) return array();

        if ($resource){
            $networklabels = array_keys($this->resourceOwners, $resource);
        } else {
            $networklabels = array($this::LABEL_SOCIAL_NETWORK);
            if (!$all){
                $networklabels = array();
                $unconnected = $this->tm->getUnconnectedNetworks($userId);
                foreach ($unconnected as $network)
                {
                    $networklabels = array_merge($networklabels, array_keys($this->resourceOwners, $network));
                }
            }

        }
        if (empty($networklabels)){
            return array();
        }

        $socialProfiles = array();

        foreach ($networklabels as $networklabel){
            $qb = $this->gm->createQueryBuilder();
            $qb->match('(u:User{qnoow_id:{userId}})')
                ->match('(u)-[hsn:HAS_SOCIAL_NETWORK]->(sn:'.$networklabel.')')
                ->returns('hsn.url as url, labels(sn) as network');
            $qb->setParameters(array(
                'userId' => (integer)$userId,
            ));
            $query = $qb->getQuery();
            $result = $query->getResultSet();

            /* @var $row Row */
            foreach ($result as $row) {
                $labels = $row->offsetGet('network');
                foreach ($labels as $network) {
                    if ($network !== $this::LABEL_SOCIAL_NETWORK) {

                        $resourceOwner = array_key_exists($network, $this->resourceOwners) ?
                            $this->resourceOwners[$network] : null;

                        $socialProfiles[] = array(
                            'id' => $userId,
                            'url' => $row->offsetGet('url'),
                            'resourceOwner' => $resourceOwner,
                        );
                    }
                }
            }
        }

        return $socialProfiles;
    }

    protected function initializeLookUpData($userData)
    {
        $lookUpData = $userData;
        if(array_key_exists('twitterUsername', $lookUpData))
            unset($lookUpData['twitterUsername']);
        if(array_key_exists('facebookUsername', $lookUpData))
            unset($lookUpData['facebookUsername']);

        return $lookUpData;
    }

    protected function getByEmail($email)
    {
        return $this->get(LookUpData::LOOKED_UP_BY_EMAIL, $email);
    }

    protected function getByTwitterUsername($twitterUsername)
    {
        return $this->get(LookUpData::LOOKED_UP_BY_TWITTER_USERNAME, $twitterUsername);
    }

    protected function getByFacebookUsername($facebookUsername)
    {
        return $this->get(LookUpData::LOOKED_UP_BY_FACEBOOK_USERNAME, $facebookUsername);
    }

    protected function get($lookUpType, $lookUpValue)
    {
        $mergedData = array();
        $previousLookUpDataArray = array();
        foreach(LookUpData::getApiResourceTypes() as $apiResource) {
            $lookUpQuery = array(
                'lookedUpType' => $lookUpType,
                'lookedUpValue' => $lookUpValue,
                'apiResource' => $apiResource,
            );
            $mergedData = $this->getMergedData($previousLookUpDataArray, $lookUpQuery);
            $previousLookUpDataArray = $mergedData;
        }

        return $mergedData;
    }

    protected function getMergedData(array $previousLookUpDataArray, $lookUpQuery)
    {
        $lookUpData = $this->em->getRepository('\Model\Entity\LookUpData')->findOneBy($lookUpQuery);
        $lookUpDataArray = array();

        if(! $lookUpData instanceof LookUpData) {
            $lookUpData = $this->createLookUpData($lookUpQuery['apiResource'], $lookUpQuery['lookedUpType'], $lookUpQuery['lookedUpValue']);
        }
        elseif(is_array($lookUpData->getResponse()) && count($lookUpData->getResponse()) > 0) {
            $lookUpDataArray = $this->getCachedResponse($lookUpData->getResponse(), $lookUpQuery['apiResource']);
        }

        if(count($lookUpData->getResponse()) < 1) {
            $lookUpDataArray = $this->getFromApiResource($lookUpData);
            $lookUpData->setResponse(isset($lookUpDataArray['response']) ? $lookUpDataArray['response'] : array());
        }
        $mergedData = ! empty($previousLookUpDataArray) ? $this->merge($previousLookUpDataArray, $lookUpDataArray) : $lookUpDataArray;

        $this->em->persist($lookUpData);
        $this->em->flush();

        return $mergedData;
    }

    protected function createLookUpData($apiResource, $lookUpType, $value)
    {
        $lookUpData = new LookUpData();
        $lookUpData->setApiResource($apiResource);
        $lookUpData->setLookedUpType($lookUpType);
        $lookUpData->setLookedUpValue($value);
        $this->em->persist($lookUpData);
        $this->em->flush();

        return $lookUpData;
    }

    protected function getCachedResponse($response, $apiResource)
    {
        switch($apiResource) {
            case LookUpData::FULLCONTACT_API_RESOURCE:
                $lookUpDataArray = $this->fullContact->getProcessedResponse($response);
                break;
            case LookUpData::PEOPLEGRAPH_API_RESOURCE:
                $lookUpDataArray = $this->peopleGraph->getProcessedResponse($response);
                break;
            default:
                $lookUpDataArray = $this->fullContact->getProcessedResponse($response);
        }

        return $lookUpDataArray;
    }

    protected function getFromApiResource(LookUpData $lookUpData)
    {
        switch($lookUpData->getApiResource()) {
            case LookUpData::FULLCONTACT_API_RESOURCE:
                $lookUpDataArray = $this->fullContact->get($lookUpData->getLookedUpValue(), $lookUpData->getLookedUpType(), $lookUpData->getHash());
                break;
            case LookUpData::PEOPLEGRAPH_API_RESOURCE:
                $lookUpDataArray = $this->peopleGraph->get($lookUpData->getLookedUpValue(), $lookUpData->getLookedUpType(), $lookUpData->getHash());
                break;
            default:
                $lookUpDataArray = $this->fullContact->get($lookUpData->getLookedUpValue(), $lookUpData->getLookedUpType(), $lookUpData->getHash());
        }

        return $lookUpDataArray;
    }

    public function merge(array $lookUpData1, array $lookUpData2)
    {
        if(! isset($lookUpData1['name']) && isset($lookUpData2['name'])) {
            $lookUpData1['name'] = $lookUpData2['name'];
        }
        if(! isset($lookUpData1['email']) && isset($lookUpData2['email'])) {
            $lookUpData1['email'] = $lookUpData2['email'];
        }
        if(! isset($lookUpData1['gender']) && isset($lookUpData2['gender'])) {
            $lookUpData1['gender'] = $lookUpData2['gender'];
        }
        if(! isset($lookUpData1['location']) && isset($lookUpData2['location'])) {
            $lookUpData1['location'] = $lookUpData2['location'];
        }
        if(isset($lookUpData2['socialProfiles']) && ! empty($lookUpData2['socialProfiles'])) {
            if(! isset($lookUpData1['socialProfiles'])) {
                $lookUpData1['socialProfiles'] = array();
            }
            foreach($lookUpData2['socialProfiles'] as $index => $socialProfile) {
                if(! isset($lookUpData1['socialProfiles'][$index]))
                    $lookUpData1['socialProfiles'][$index] = $socialProfile;
            }
        }

        return $lookUpData1;
    }

    protected function setSocialProfiles(array $socialProfiles, $id)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User)')
            ->where('u.qnoow_id = { id }')
            ->setParameter('id', (int)$id);

        $counter = 0;
        foreach($socialProfiles as $resource => $url) {
            $counter++;
            $label = ucfirst(str_replace('.', '', str_replace(' ', '', $resource)));
            $resourceNode = $label . $this::LABEL_SOCIAL_NETWORK;
            $qb->merge('(sn' . $counter . ':'.$this::LABEL_SOCIAL_NETWORK.':' . $resourceNode . ')')
                ->merge('(u)-[hsn' . $counter . ':HAS_SOCIAL_NETWORK {url: { url' . $counter . ' }}]->(sn' . $counter . ')')
                ->setParameter('url' . $counter, $url);
        }

        $query = $qb->getQuery();
        $query->getResultSet();
    }

    protected function getServiceFromApiResource($apiResource)
    {
        switch($apiResource) {
            case LookUpData::FULLCONTACT_API_RESOURCE:
                $service = $this->fullContact;
                break;
            case LookUpData::PEOPLEGRAPH_API_RESOURCE:
                $service = $this->peopleGraph;
                break;
            default:
                $service = null;
        }

        return $service;
    }

    protected function isCompleted(array $lookUpData)
    {
        if(isset($lookUpData['email']) && isset($lookUpData['gender']) && isset($lookUpData['location']) && isset($lookUpData['socialProfiles'])) {
            return true;
        }

        return false;
    }

    protected function completeLookUpTypes($userData, $lookUpData)
    {
        if(! $this->isEmailSet($userData) && $this->isEmailSet($lookUpData)) {
            $userData['email'] = $lookUpData['email'];
        }
        if(! $this->isTwitterUsernameSetInUserData($userData) && $this->isTwitterUsernameSetInLookUpData($lookUpData)) {
            $userData['twitterUsername'] = $this->getTwitterUsernameFromLookUpData($lookUpData);
        }
        if(! $this->isFacebookUsernameSetInUserData($userData) && $this->isFacebookUsernameSetInLookUpData($lookUpData)) {
            $userData['facebookUsername'] = $this->getFacebookUsernameFromLookUpData($lookUpData);
        }

        return $userData;
    }

    protected function isEmailSet(array $data)
    {
        if(isset($data['email']) && $data['email'])
            return true;

        return false;
    }

    protected function isTwitterUsernameSetInLookUpData(array $lookUpData)
    {
        if(isset($lookUpData['socialProfiles']['twitter']) && $lookUpData['socialProfiles']['twitter'])
            return true;

        return false;
    }

    protected function isFacebookUsernameSetInLookUpData(array $lookUpData)
    {
        if(isset($lookUpData['socialProfiles']['facebook']) && $lookUpData['socialProfiles']['facebook'])
            return true;

        return false;
    }

    protected function isTwitterUsernameSetInUserData(array $userData)
    {
        if(isset($userData['twitterUsername']) && $userData['twitterUsername'])
            return true;

        return false;
    }

    protected function isFacebookUsernameSetInUserData(array $userData)
    {
        if(isset($userData['facebookUsername']) && $userData['facebookUsername'])
            return true;

        return false;
    }

    protected function getTwitterUsernameFromLookUpData(array $lookUpData)
    {
        $twitterUsernameUrl = $lookUpData['socialProfiles']['twitter'];

        return substr($twitterUsernameUrl, strrpos($twitterUsernameUrl, '/') + 1);
    }

    protected function getFacebookUsernameFromLookUpData(array $lookUpData)
    {
        $facebookUsernameUrl = $lookUpData['socialProfiles']['facebook'];

        return substr($facebookUsernameUrl, strrpos($facebookUsernameUrl, '/') + 1);
    }

    protected function showOutputMessageIfDefined(OutputInterface $outputInterface = null, $message)
    {
        if($outputInterface)
            $outputInterface->writeln($message);
    }

    protected function dispatchSocialNetworksAddedEvent($id, $socialProfiles)
    {
        $event = new LookUpSocialNetworksEvent($id, $socialProfiles);
        $this->dispatcher->dispatch(\AppEvents::SOCIAL_NETWORKS_ADDED, $event);
    }
}
