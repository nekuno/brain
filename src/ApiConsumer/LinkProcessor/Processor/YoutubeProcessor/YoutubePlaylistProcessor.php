<?php

namespace ApiConsumer\LinkProcessor\Processor\YoutubeProcessor;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use Model\Video;

class YoutubePlaylistProcessor extends AbstractYoutubeProcessor
{
    protected $itemApiUrl = 'youtube/v3/playlists';
    protected $itemApiParts = 'snippet,status';

    public function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        parent::hydrateLink($preprocessedLink, $data);

        $link = Video::buildFromLink($preprocessedLink->getLink());
        $link->setEmbedId($preprocessedLink->getResourceItemId());
        $link->setEmbedType('youtube');

        $preprocessedLink->setLink($link);
    }

    function getItemIdFromParser($url)
    {
        return $this->parser->getPlaylistId($url);
    }

    protected function requestSpecificItem($id)
    {
        return $this->resourceOwner->requestPlaylist($id);
    }

}