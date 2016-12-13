<?php

namespace ApiConsumer\LinkProcessor\Processor\TwitterProcessor;

use ApiConsumer\Exception\CannotProcessException;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use Model\Creator;
use Model\User\TokensModel;

class TwitterProfileProcessor extends AbstractTwitterProfileProcessor
{

    public function requestItem(PreprocessedLink $preprocessedLink)
    {
        $userName = $this->getItemId($preprocessedLink->getUrl());
        $users = $this->resourceOwner->lookupUsersBy('screen_name', $userName);

        //Response validation
        if (empty($users)) {
            throw new CannotProcessException($preprocessedLink->getUrl());
        }

        return $users[0];
    }

    /**
     * @param $preprocessedLinks PreprocessedLink[]
     * @return array|bool
     * //TODO: Generalize "batch processing" logic?
     */
    public function processMultipleProfiles($preprocessedLinks)
    {

        $userNames = array();
        foreach ($preprocessedLinks as $key => $preprocessedLink) {

            $link = $preprocessedLink->getLink();

            if ($preprocessedLink->getSource() == TokensModel::TWITTER
                && $link->isComplete() && !($link->getProcessed() !== false)
            ) {
                unset($preprocessedLinks[$key]);
            }

            $userName = $this->parser->getProfileId($preprocessedLink->getUrl());
            $userNames[] = $userName['screen_name'];
        }

        $users = $this->resourceOwner->lookupUsersBy('screen_name', $userNames);
        if (empty($users)) {
            return false;
        }

        $links = array();
        foreach ($users as $user) {
            $links[] = Creator::buildFromArray($this->resourceOwner->buildProfileFromLookup($user));
        }

        return $links;
    }
}