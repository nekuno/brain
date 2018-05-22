<?php

namespace ApiConsumer\LinkProcessor\Processor\SpotifyProcessor;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use Model\Link\Audio;

class SpotifyAlbumProcessor extends AbstractSpotifyProcessor
{
    protected function requestItem(PreprocessedLink $preprocessedLink)
    {
        $id = $this->getItemId($preprocessedLink->getUrl());
        $album = $this->resourceOwner->requestAlbum($id, $preprocessedLink->getToken());

        return $album;
    }

    public function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        parent::hydrateLink($preprocessedLink, $data);
        $link = $preprocessedLink->getFirstLink();

        $artistList = $this->buildArtistList($data);

        $link->setTitle($data['name']);
        $link->setDescription('By: ' . implode(', ', $artistList));

        $link = Audio::buildFromLink($link);
        $link->setEmbedId($data['uri']);
        $link->setEmbedType('spotify');

        $preprocessedLink->setFirstLink($link);
    }

    public function addTags(PreprocessedLink $preprocessedLink, array $data)
    {
        parent::addTags($preprocessedLink, $data);

        $link = $preprocessedLink->getFirstLink();

        if (isset($data['name']) && isset($data['genres']) && isset($data['artists'])) {
            foreach ($data['genres'] as $genre) {
                $link->addTag($this->buildMusicalGenreTag($genre));
            }

            $artistList = array();
            foreach ($data['artists'] as $artist) {
                $link->addTag($this->buildArtistTag($artist));

                $artistList[] = $artist['name'];
            }

            $link->addTag($this->buildAlbumTag($data));
        }
    }

}