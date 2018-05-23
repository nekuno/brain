<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\SteamUrlParser;
use ApiConsumer\ResourceOwner\SteamResourceOwner;
use Model\Link\Link;
use Model\Token\Token;

class SteamGamesFetcher extends AbstractFetcher
{
    protected $url = 'IPlayerService/GetOwnedGames/v1';

    protected function getQuery()
    {
        return array(
            'include_appinfo' => 1,
            'include_played_free_games' => 1,
        );
    }

    public function fetchLinksFromUserFeed(Token $token)
    {
        $this->setToken($token);

        /** @var SteamResourceOwner $resourceOwner */
        $resourceOwner = $this->resourceOwner;
        $response = $resourceOwner->requestAsUser($this->url, $this->getQuery(), $token);

        $games = isset($response['response']['games']) ? $response['response']['games'] : array();

        return $this->parseLinks($games);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAsClient($username)
    {
        return array();
    }

    /**
     * @inheritdoc
     */
    protected function parseLinks(array $response)
    {
        $preprocessedLinks = array();

        foreach ($response as $item) {
            $appId = $item['appid'];

            $type = SteamUrlParser::getGameProcessor();
            $link = new Link();
            $link->setId($appId);
            $link->setUrl("https://store.steampowered.com/app/" . $appId);
            $link->setTitle($item['name']);
            $link->setThumbnail($this->getThumbnail($item));

            $preprocessedLink = new PreprocessedLink($link->getUrl());
            $preprocessedLink->setFirstLink($link);
            $preprocessedLink->setType($type);
            $preprocessedLink->setSource($this->resourceOwner->getName());
            $preprocessedLink->setResourceItemId($appId);
            $preprocessedLink->setToken($this->getToken());
            $preprocessedLinks[] = $preprocessedLink;
        }

        return $preprocessedLinks;
    }

    public function getThumbnail($item)
    {
        $gameId = $item['appid'];
        if (isset($item['img_logo_url']) && $item['img_logo_url']) {
            $hash = $item['img_logo_url'];
            return "http://media.steampowered.com/steamcommunity/public/images/apps/$gameId/$hash.jpg";
        }

        return null;
    }
}