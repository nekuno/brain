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

abstract class AbstractTwitterProfileProcessor extends AbstractProcessor implements BatchProcessorInterface
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

    public function requestItem(PreprocessedLink $preprocessedLink)
    {
        $userId = $this->getUserId($preprocessedLink);
        $token = $preprocessedLink->getSource() == TokensModel::TWITTER ? $preprocessedLink->getToken() : array();
        $key = array_keys($userId)[0];

        $users = $this->resourceOwner->lookupUsersBy($key, array($userId[$key]), $token);

        //Response validation
        if (empty($users)) {
            throw new CannotProcessException($preprocessedLink->getUrl());
        }

        return $users[0];
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

    protected function buildLinks(array $usersLookup)
    {
        if (empty($usersLookup)) {
            return array();
        }

        $links = array();
        foreach ($usersLookup as $user) {
            $links[] = Creator::buildFromArray($this->resourceOwner->buildProfileFromLookup($user));
        }

        return $links;
    }

    public function requestBatchLinks()
    {
        $userIds = $this->getUserIdsFromBatch();

        $token = $this->getTokenFromBatch();

        $users = $this->requestUsers($userIds, $token);

        $links = $this->buildLinks($users);

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

    protected function requestUsers(array $userIds, $token)
    {
        $users = array();
        foreach ($userIds as $key => $ids) {
            $users = array_merge($users, $this->resourceOwner->lookupUsersBy($key, $ids, $token));
        }

        return $users;
    }

}