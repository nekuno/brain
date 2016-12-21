<?php

namespace ApiConsumer\LinkProcessor\Processor\TwitterProcessor;

use ApiConsumer\Exception\CannotProcessException;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\AbstractProcessor;
use ApiConsumer\LinkProcessor\Processor\BatchProcessorInterface;
use ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser;
use ApiConsumer\ResourceOwner\TwitterResourceOwner;
use Model\Creator;
use Model\User\TokensModel;

abstract class TwitterProfileProcessor extends AbstractProcessor implements BatchProcessorInterface
{
    /**
     * @var TwitterResourceOwner
     */
    protected $resourceOwner;

    /**
     * @var TwitterUrlParser
     */
    protected $parser;

    /**
     * @var PreprocessedLink[]
     */
    protected $batch = array();

    protected function requestItem(PreprocessedLink $preprocessedLink)
    {
        $userId = $this->getUserId($preprocessedLink);
        $token = $preprocessedLink->getSource() == TokensModel::TWITTER ? $preprocessedLink->getToken() : array();
        $key = array_keys($userId)[0];

        $response = $this->resourceOwner->lookupUsersBy($key, array($userId[$key]), $token);

        //Response validation
        if (empty($response)) {
            throw new CannotProcessException($preprocessedLink->getUrl());
        }

        return $response;
    }

    public function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        $preprocessedLink->setFirstLink(Creator::buildFromArray($this->resourceOwner->buildProfileFromLookup($data)));
    }

    public function getImages(PreprocessedLink $preprocessedLink, array $data)
    {
        return isset($data['profile_image_url']) ? array(str_replace('_normal', '', $data['profile_image_url'])) : array();
    }

    protected function getUserId(PreprocessedLink $preprocessedLink)
    {
        return $this->getItemId($preprocessedLink->getUrl());
    }

    protected function getItemIdFromParser($url)
    {
        return $this->parser->getProfileId($url);
    }

    public function addToBatch(PreprocessedLink $preprocessedLink)
    {
        $this->batch[] = $preprocessedLink;
    }

    public function needToRequest()
    {
        return count($this->batch) >= TwitterResourceOwner::PROFILES_PER_LOOKUP;
    }

    protected function cleanBatch()
    {
        $this->batch = array();
    }

    protected function buildLinks(array $responses)
    {
        if (empty($responses)) {
            return array();
        }

        $links = array();
        foreach ($responses as $response)
        {
            foreach ($response as $user) {
                $links[] = Creator::buildFromArray($this->resourceOwner->buildProfileFromLookup($user));
            }
        }


        return $links;
    }

    public function requestBatchLinks()
    {
        $userIds = $this->getUserIdsFromBatch();

        $token = $this->getTokenFromBatch();

        $responses = $this->requestLookup($userIds, $token);

        $links = $this->buildLinks($responses);

        $this->cleanBatch();

        return $links;
    }

    protected function getUserIdsFromBatch()
    {
        $userIds = array('user_id' => array(), 'screen_name' => array());
        foreach ($this->batch as $key => $preprocessedLink) {

            $link = $preprocessedLink->getFirstLink();

            if ($preprocessedLink->getSource() == TokensModel::TWITTER
                && $link->isComplete() && !($link->getProcessed() !== false)
            ) {
                unset($this->batch[$key]);
            }

            $userId = $this->parser->getProfileId($preprocessedLink->getUrl());
            $key = array_keys($userId)[0];
            $userIds[$key][] = $userId;
        }

        return $userIds;
    }

    protected function getTokenFromBatch()
    {
        foreach ($this->batch as $preprocessedLink) {
            if (!empty($token = $preprocessedLink->getToken())) {
                return $token;
            }
        }

        return array();
    }

    protected function requestLookup(array $userIds, $token)
    {
        $lookupResponses = array();
        foreach ($userIds as $key => $ids) {
            $lookupResponses = array_merge($lookupResponses, $this->resourceOwner->lookupUsersBy($key, $ids, $token));
        }

        return $lookupResponses;
    }

}