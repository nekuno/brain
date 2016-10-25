<?php

namespace ApiConsumer\LinkProcessor\Processor\SpotifyProcessor;

use ApiConsumer\LinkProcessor\Processor\AbstractProcessor;
use ApiConsumer\LinkProcessor\UrlParser\SpotifyUrlParser;
use ApiConsumer\ResourceOwner\SpotifyResourceOwner;

abstract class AbstractSpotifyProcessor extends AbstractProcessor
{
    /**
     * @var SpotifyUrlParser
     */
    protected $parser;

    /**
     * @var SpotifyResourceOwner
     */
    protected $resourceOwner;

    protected function buildArtistTag($artist)
    {
        $tag = array();
        $tag['name'] = $artist['name'];
        $tag['additionalLabels'][] = 'Artist';
        $tag['additionalFields']['spotifyId'] = $artist['id'];

        return $tag;
    }

    protected function buildAlbumTag($album)
    {
        $tag = array();
        $tag['name'] = $album['name'];
        $tag['additionalLabels'][] = 'Album';
        $tag['additionalFields']['spotifyId'] = $album['id'];

        return $tag;
    }

    protected function buildMusicalGenreTag($genre)
    {
        $tag = array();
        $tag['name'] = $genre;
        $tag['additionalLabels'][] = 'MusicalGenre';

        return $tag;
    }

    protected function buildSongTag($track) {
        $tag = array();
        $tag['name'] = $track['name'];
        $tag['additionalLabels'][] = 'Song';
        $tag['additionalFields']['spotifyId'] = $track['id'];
        if (isset($track['external_ids']['isrc'])) {
            $tag['additionalFields']['isrc'] = $track['external_ids']['isrc'];
        }

        return $tag;
    }

    protected function getItemIdFromParser($url)
    {
        return $this->parser->getSpotifyId($url);
    }

    protected function buildArtistList($data)
    {
        $artistList = array();
        foreach ($data['artists'] as $artist) {
            $artistList[] = $artist['name'];
        }

        return $artistList;
    }
}