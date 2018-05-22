<?php

namespace ApiConsumer\LinkProcessor\Processor\SpotifyProcessor;

use ApiConsumer\Exception\CannotProcessException;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\SynonymousParameters;
use ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser;
use Model\Link\Audio;

class SpotifyTrackProcessor extends AbstractSpotifyProcessor
{
    protected function requestItem(PreprocessedLink $preprocessedLink)
    {
        $id = $this->getItemId($preprocessedLink->getUrl());
        $preprocessedLink->setResourceItemId($id);

        $track = $this->resourceOwner->requestTrack($id, $preprocessedLink->getToken());

        return $track;
    }

    protected function isValidResponse(array $response)
    {
        $hasAlbumData = isset($response['album']);
        $hasName = isset($response['name']);
        $hasArtistsData = isset($response['artists']);

        return parent::isValidResponse($response) && $hasAlbumData && $hasName && $hasArtistsData;
    }

    public function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        parent::hydrateLink($preprocessedLink, $data);
        $link = $preprocessedLink->getFirstLink();

        $artistList = $this->buildArtistList($data);

        $link->setTitle($data['name']);
        $link->setDescription($data['album']['name'] . ' : ' . implode(', ', $artistList));

        $link = Audio::buildFromLink($link);
        $link->setEmbedType('spotify');
        $link->setEmbedId($data['uri']);

        $preprocessedLink->setFirstLink($link);
    }

    public function getImages(PreprocessedLink $preprocessedLink, array $data)
    {
        $albumData = $data['album'];

        return parent::getImages($preprocessedLink, $albumData);
    }

    public function addTags(PreprocessedLink $preprocessedLink, array $data)
    {
        $link = $preprocessedLink->getFirstLink();

        $album = $data['album'];
        if (isset($album['genres'])) {
            foreach ($album['genres'] as $genre) {
                $link->addTag($this->buildMusicalGenreTag($genre));
            }
        }

        foreach ($data['artists'] as $artist) {
            $link->addTag($this->buildArtistTag($artist));
        }

        $link->addTag($this->buildAlbumTag($album));
        $link->addTag($this->buildSongTag($data));
    }

    public function getSynonymousParameters(PreprocessedLink $preprocessedLink, array $data)
    {
        $artistList = $this->buildArtistList($data);
        $song = $data['name'];

        $queryString = implode(', ', $artistList) . ' ' . $song;

        $synonymousParameters = new SynonymousParameters();
        $synonymousParameters->setQuantity(3);
        $synonymousParameters->setQuery($queryString);
        $synonymousParameters->setComparison($preprocessedLink->getFirstLink()->getTitle());
        $synonymousParameters->setType(YoutubeUrlParser::VIDEO_URL);

        $preprocessedLink->setSynonymousParameters($synonymousParameters);
    }
}