<?php

namespace ApiConsumer\LinkProcessor\Processor\SpotifyProcessor;

use ApiConsumer\LinkProcessor\PreprocessedLink;

class SpotifyArtistProcessor extends AbstractSpotifyProcessor
{
    function requestItem(PreprocessedLink $preprocessedLink)
    {
        $id = $this->getItemId($preprocessedLink->getCanonical());

        $artist = $this->resourceOwner->requestArtist($id);

        return $artist;
    }

    function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        $link = $preprocessedLink->getLink();

        $link->setTitle($data['name']);
        //TODO: Check thumbnail & description from scrapper
    }

    function addTags(PreprocessedLink $preprocessedLink, array $data)
    {
        parent::addTags($preprocessedLink, $data);

        $link = $preprocessedLink->getLink();

        if (isset($data['name']) && isset($data['genres'])) {
            foreach ($data['genres'] as $genre) {
                $link->addTag($this->buildMusicalGenreTag($genre));
            }

            //TODO: Check consistency of this with channels
            $link->addTag($this->buildArtistTag($data));
        }
    }

}