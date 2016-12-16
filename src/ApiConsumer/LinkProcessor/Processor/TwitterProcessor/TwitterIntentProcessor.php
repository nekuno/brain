<?php

namespace ApiConsumer\LinkProcessor\Processor\TwitterProcessor;

use ApiConsumer\Exception\CannotProcessException;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use Model\User\TokensModel;

class TwitterIntentProcessor extends AbstractTwitterProfileProcessor
{
    public function requestItem(PreprocessedLink $preprocessedLink)
    {
        $userId = $preprocessedLink->getResourceItemId() ?
            array('user_id' => $preprocessedLink->getResourceItemId())
            :
            $this->getItemId($preprocessedLink->getUrl());

        $key = array_keys($userId)[0];

        $token = $preprocessedLink->getSource() == TokensModel::TWITTER ? $preprocessedLink->getToken() : array();
        $users = $this->resourceOwner->lookupUsersBy($key, array($userId[$key]), $token);

        //Response validation
        if (empty($users)) {
            throw new CannotProcessException($preprocessedLink->getUrl());
        }

        return $users[0];
    }

    public function requestBatchLinks()
    {
        $userIds = array('user_id' => array(), 'screen_name' => array());
        foreach ($this->batch as $key => $preprocessedLink) {

            $link = $preprocessedLink->getFirstLink();

            if ($link->isComplete() && $link->getProcessed() !== false) {
                unset($this->batch[$key]);
            }

            $userId = $preprocessedLink->getResourceItemId() ?
                array('user_id' => $preprocessedLink->getResourceItemId()) :
                $this->parser->getProfileId($preprocessedLink->getUrl());

            $key = array_keys($userId)[0];
            $userIds[$key][] = $userId;
        }

        $users = array();
        foreach ($userIds as $key => $ids) {
            $users = array_merge($users, $this->resourceOwner->lookupUsersBy($key, $ids));
        }

        if (empty($users)) {
            return false;
        }

        $links = array();
        foreach ($users as $user) {
            $links[] = $this->resourceOwner->buildProfileFromLookup($user);
        }

        return $links;
    }
}