<?php

namespace ApiConsumer\LinkProcessor\Processor\SteamProcessor;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\SteamUrlParser;
use Model\Link\Game;

class SteamGameProcessor extends AbstractSteamProcessor
{
    protected function requestItem(PreprocessedLink $preprocessedLink)
    {
        if (!$gameId = $preprocessedLink->getResourceItemId()) {
            $firstLink = $preprocessedLink->getFirstLink();
            $gameId = SteamUrlParser::getGameId($firstLink->getUrl());
        }
        $response = $this->resourceOwner->requestGame($gameId);

        return isset($response['game']) ? $response['game'] : array();
    }

    public function isValidResponse(array $response)
    {
        return true;
    }

    public function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        parent::hydrateLink($preprocessedLink, $data);
        $link = $preprocessedLink->getFirstLink();
        $creator = Game::buildFromLink($link);
        if (isset($data['gameName']) && $data['gameName']) {
            $creator->setTitle($data['gameName']);
            $creator->setDescription(null);
        }

        $preprocessedLink->setFirstLink($creator);
    }

    public function getImages(PreprocessedLink $preprocessedLink, array $data)
    {
        $firstLink = $preprocessedLink->getFirstLink();
        if (!$firstLink->getThumbnailLarge()) {
            if (!$gameId = $preprocessedLink->getResourceItemId()) {
                $gameId = SteamUrlParser::getGameId($firstLink->getUrl());
            }

            $thumbnail = $this->resourceOwner->requestGameImage($gameId);
            $firstLink->setThumbnail($thumbnail);
        }

        return parent::getImages($preprocessedLink, $data);
    }
}