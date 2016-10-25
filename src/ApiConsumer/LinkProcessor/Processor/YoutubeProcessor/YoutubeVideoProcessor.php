<?php

namespace ApiConsumer\LinkProcessor\Processor\YoutubeProcessor;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use Model\Video;

class YoutubeVideoProcessor extends AbstractYoutubeProcessor
{

    public function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        parent::hydrateLink($preprocessedLink, $data);

        $link = $preprocessedLink->getLink();
        $itemId = $preprocessedLink->getResourceItemId();

        $link->setThumbnail('https://img.youtube.com/vi/' . $itemId . '/mqdefault.jpg');
        $link = Video::buildFromLink($link);
        $link->setEmbedId($itemId);
        $link->setEmbedType('youtube');

        $preprocessedLink->setLink($link);
    }

    function getItemIdFromParser($url)
    {
        return $this->parser->getVideoId($url);
    }

    function addTags(PreprocessedLink $preprocessedLink, array $item)
    {
        $link = $preprocessedLink->getLink();

        if (isset($item['topicDetails']['topicIds'])) {
            foreach ($item['topicDetails']['topicIds'] as $tagName) {
                $link->addTag(array(
                    'name' => $tagName,
                    'additionalLabels' => array('Freebase'),
                ));
            }
        }
    }

    protected function requestSpecificItem($id)
    {
        return $this->resourceOwner->requestVideo($id);
    }

}