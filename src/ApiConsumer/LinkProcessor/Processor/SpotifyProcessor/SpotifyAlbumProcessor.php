<?php

namespace ApiConsumer\LinkProcessor\Processor\SpotifyProcessor;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use Model\Audio;

class SpotifyAlbumProcessor extends AbstractSpotifyProcessor
{

    function requestItem(PreprocessedLink $preprocessedLink)
    {
        $id = $this->getItemId($preprocessedLink->getCanonical());

        $album = $this->resourceOwner->requestAlbum($id);

        return $album;
    }

    function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        $link = $preprocessedLink->getLink();

        $artistList = $this->buildArtistList($data);

        $link->setTitle($data['name']);
        $link->setDescription('By: ' . implode(', ', $artistList));

        $link = Audio::buildFromLink($link);
        $link->setEmbedId($data['uri']);
        $link->setEmbedType('spotify');

        $preprocessedLink->setLink($link);
    }

    function addTags(PreprocessedLink $preprocessedLink, array $data)
    {
        parent::addTags($preprocessedLink, $data);

        $link = $preprocessedLink->getLink();

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