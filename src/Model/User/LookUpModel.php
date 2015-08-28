<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Model\User;

use Doctrine\ORM\EntityManager;
use Model\Neo4j\GraphManager;
use Model\Entity\LookUpData;
use Service\LookUp\LookUp;
use Service\LookUp\LookUpFullContact;
use Service\LookUp\LookUpPeopleGraph;
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

    public function __construct(GraphManager $gm, EntityManager $em, LookUpFullContact $fullContact, LookUpPeopleGraph $peopleGraph)
    {
        $this->gm = $gm;
        $this->em = $em;
        $this->fullContact = $fullContact;
        $this->peopleGraph = $peopleGraph;
    }

    public function getByEmail($email)
    {
        return $this->get(LookUpData::LOOKED_UP_BY_EMAIL, $email);
    }

    public function getByTwitterUsername($twitterUsername)
    {
        return $this->get(LookUpData::LOOKED_UP_BY_TWITTER_USERNAME, $twitterUsername);
    }

    public function getByFacebookUsername($facebookUsername)
    {
        return $this->get(LookUpData::LOOKED_UP_BY_FACEBOOK_USERNAME, $facebookUsername);
    }

    public function setByEmail($id, $email)
    {
        $lookUpData = $this->get(LookUpData::LOOKED_UP_BY_EMAIL, $email);

        if(isset($lookUpData['socialProfiles']) && ! empty($lookUpData['socialProfiles'])) {
            $this->setSocialProfiles($lookUpData['socialProfiles'], $id);
            return $lookUpData['socialProfiles'];
        }

        return array();
    }

    public function setByTwitterUsername($id, $twitterUsername)
    {
        $lookUpData = $this->get(LookUpData::LOOKED_UP_BY_TWITTER_USERNAME, $twitterUsername);

        if(isset($lookUpData['socialProfiles']) && ! empty($lookUpData['socialProfiles'])) {
            $this->setSocialProfiles($lookUpData['socialProfiles'], $id);
            return $lookUpData['socialProfiles'];
        }

        return array();
    }

    public function setByFacebookUsername($id, $facebookUsername)
    {
        $lookUpData = $this->get(LookUpData::LOOKED_UP_BY_FACEBOOK_USERNAME, $facebookUsername);

        if(isset($lookUpData['socialProfiles']) && ! empty($lookUpData['socialProfiles'])) {
            $this->setSocialProfiles($lookUpData['socialProfiles'], $id);
            return $lookUpData['socialProfiles'];
        }

        return array();
    }

    public function setFromWebHook(Request $request)
    {
        $id = $request->get('webHookId');
        if($lookUpData = $this->em->getRepository('\Model\Entity\LookUpData')->findOneBy(array('id' => (int)$id))) {
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
                $lookUpDataArray = $this->fullContact->get($lookUpData->getLookedUpValue(), $lookUpData->getLookedUpType(), $lookUpData->getId());
                break;
            case LookUpData::PEOPLEGRAPH_API_RESOURCE:
                $lookUpDataArray = $this->peopleGraph->get($lookUpData->getLookedUpValue(), $lookUpData->getLookedUpType(), $lookUpData->getId());
                break;
            default:
                $lookUpDataArray = $this->fullContact->get($lookUpData->getLookedUpValue(), $lookUpData->getLookedUpType(), $lookUpData->getId());
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
            $resourceNode = $label . 'SocialNetwork';
            $qb->merge('(sn' . $counter . ':SocialNetwork:' . $resourceNode . ')')
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
}
