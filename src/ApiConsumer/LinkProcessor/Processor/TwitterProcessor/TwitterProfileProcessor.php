<?php

namespace ApiConsumer\LinkProcessor\Processor\TwitterProcessor;

use ApiConsumer\Exception\CannotProcessException;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\AbstractProcessor;
use ApiConsumer\LinkProcessor\Processor\BatchProcessorInterface;
use ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser;
use ApiConsumer\ResourceOwner\TwitterResourceOwner;
use Model\Creator;
use Model\Link;
use Model\User\TokensModel;

class TwitterProfileProcessor extends AbstractProcessor implements BatchProcessorInterface
{
    /**
     * @var TwitterResourceOwner
     */
    protected $resourceOwner;

    /**
     * @var TwitterUrlParser
     */
    protected $parser;

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

        return reset($response);
    }

    public function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        $profileArray = $this->resourceOwner->buildProfileFromLookup($data);
        $preprocessedLink->setFirstLink(Creator::buildFromArray($profileArray));

        $id = isset($data['id_str']) ? (int)$data['id_str'] : $data['id'];
        $preprocessedLink->setResourceItemId($id);
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

    public function needToRequest(array $batch)
    {
        return count($batch) >= TwitterResourceOwner::PROFILES_PER_LOOKUP;
    }

    /**
     * @param array $batch
     * @return Link[]
     */
    public function requestBatchLinks(array $batch)
    {
        $userIds = $this->getUserIdsFromBatch($batch);

        $token = $this->getTokenFromBatch($batch);

        $responses = $this->requestLookup($userIds, $token);

        $links = $this->buildLinks($responses);

        return $links;
    }

    /**
     * @param PreprocessedLink[] $batch
     * @return array
     */
    protected function getUserIdsFromBatch(array $batch)
    {
        $userIds = array('user_id' => array(), 'screen_name' => array());
        foreach ($batch as $key => $preprocessedLink) {

            $link = $preprocessedLink->getFirstLink();

            if ($preprocessedLink->getSource() == TokensModel::TWITTER
                && $link->isComplete() && !($link->getProcessed() !== false)
            ) {
                unset($batch[$key]);
            }

            $userId = $this->parser->getProfileId($preprocessedLink->getUrl());
            $key = array_keys($userId)[0];
            $userIds[$key][] = $userId[$key];
        }

        return $userIds;
    }

    /**
     * @param $batch PreprocessedLink[]
     * @return array
     */
    protected function getTokenFromBatch(array $batch)
    {
        foreach ($batch as $preprocessedLink) {
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

}