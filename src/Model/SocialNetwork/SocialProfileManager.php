<?php

namespace Model\SocialNetwork;


use Everyman\Neo4j\Query\ResultSet;
use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;
use Model\LookUp\LookUpManager;
use Model\Token\TokensManager;

class SocialProfileManager
{
    /**
     * @var TokensManager
     */
    protected $tokensModel;

    /**
     * @var GraphManager
     */
    protected $graphManager;

    /**
     * @var LookUpManager
     */
    protected $lookupModel;

    function __construct(GraphManager $graphManager, TokensManager $tokensModel, LookUpManager $lookupModel)
    {
        $this->tokensModel = $tokensModel;
        $this->graphManager = $graphManager;
        $this->lookupModel = $lookupModel;
    }


    /**
     * @return SocialProfile[]
     * @throws \Model\Neo4j\Neo4jException
     */
    public function getAllSocialProfiles()
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(u:User)')
            ->match('(u)-[hsn:HAS_SOCIAL_NETWORK]->(sn:' . LookUpManager::LABEL_SOCIAL_NETWORK . ')')
            ->returns('hsn.url as url, labels(sn) as network, u.qnoow_id as id');
        $query = $qb->getQuery();
        $result = $query->getResultSet();

        return $this->buildSocialProfile($result);
    }


    /**
     * @param $userId
     * @param string $resource specific social network
     * @param bool $all if false, only connected using Nekuno and available in LookUpModel::resourceOwners
     * @return SocialProfile[]
     * @throws \Model\Neo4j\Neo4jException
     */
    public function getSocialProfiles($userId, $resource = null, $all = false)
    {
        if (!$userId) return array();

        if ($resource) {
            $networkLabels = array_keys(LookUpManager::$resourceOwners, $resource);
        } else {
            $networkLabels = array(LookUpManager::LABEL_SOCIAL_NETWORK);
            if (!$all) {
                $networkLabels = array();
                $unconnected = $this->tokensModel->getUnconnectedNetworks($userId);
                foreach ($unconnected as $network) {
                    $networkLabels = array_merge($networkLabels, array_keys(LookUpManager::$resourceOwners, $network));
                }
            }

        }
        if (empty($networkLabels)) {
            return array();
        }

        $socialProfiles = array();

        foreach ($networkLabels as $networkLabel) {

            $qb = $this->graphManager->createQueryBuilder();
            $qb->match('(u:User{qnoow_id:{userId}})')
                ->match('(u)-[hsn:HAS_SOCIAL_NETWORK]->(sn:' . $networkLabel . ')')
                ->returns('hsn.url as url, labels(sn) as network, u.qnoow_id as id');
            $qb->setParameters(array(
                'userId' => (integer)$userId,
            ));
            $query = $qb->getQuery();
            $result = $query->getResultSet();

            $socialProfiles = array_merge($socialProfiles, $this->buildSocialProfile($result));
        }

        return $socialProfiles;
    }

    /**
     * @param $url
     * @return SocialProfile[]
     * @throws \Model\Neo4j\Neo4jException
     */
    public function getByUrl($url)
    {
        if (!$url) return array();
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(u)')
            ->match('(u)-[hsn:HAS_SOCIAL_NETWORK]->(sn:' . LookUpManager::LABEL_SOCIAL_NETWORK . ')')
            ->where('hsn.url = {url}')
            ->returns('hsn.url as url, labels(sn) as network, u.qnoow_id as id');
        $qb->setParameters(array(
            'url' => $url,
        ));
        $query = $qb->getQuery();
        $result = $query->getResultSet();

        return $this->buildSocialProfile($result);
    }

    /**
     * @param ResultSet $result
     * @return SocialProfile[]
     */
    protected function buildSocialProfile(ResultSet $result)
    {

        $socialProfiles = array();

        /* @var $row Row */
        foreach ($result as $row) {
            $labels = $row->offsetGet('network');
            foreach ($labels as $network) {
                if ($network !== LookUpManager::LABEL_SOCIAL_NETWORK
                    && $row->offsetGet('id')
                    && $row->offsetGet('url')
                )
                {
                    $resourceOwner = array_key_exists($network, LookUpManager::$resourceOwners) ?
                        LookUpManager::$resourceOwners[$network] : null;

                    $socialProfiles[] = new SocialProfile(
                        $row->offsetGet('id'),
                        $row->offsetGet('url'),
                        $resourceOwner
                    );
                }
            }
        }

        return $socialProfiles;
    }

    public function addSocialProfile(SocialProfile $profile)
    {
        $this->lookupModel->setSocialProfiles(array(
            $profile->getResource() => $profile->getUrl()
        ), $profile->getUserId());

        $this->lookupModel->dispatchSocialNetworksAddedEvent($profile->getUserId(), array(
            $profile->getResource() => $profile->getUrl()
        ));
    }
}