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

        $link = Video::buildFromLink($link);
        $link->setEmbedId($itemId);
        $link->setEmbedType('youtube');

        $preprocessedLink->setLink($link);
    }

    public function getImages(PreprocessedLink $preprocessedLink, array $data)
    {
        $itemId = $preprocessedLink->getResourceItemId();

        $imageUrls = array();
        foreach ($this->imageResolutions() as $resolution) {
            $imageUrls[] = 'https://img.youtube.com/vi/' . $itemId . '/' . $resolution;
        }

        return $imageUrls;
    }

    public function getItemIdFromParser($url)
    {
        return $this->parser->getVideoId($url);
    }

    public function addTags(PreprocessedLink $preprocessedLink, array $item)
    {
        $link = $preprocessedLink->getLink();

        if (isset($item['topicDetails']['topicIds'])) {
            foreach ($item['topicDetails']['topicIds'] as $tagName) {
                $link->addTag(
                    array(
                        'name' => $tagName,
                        'additionalLabels' => array('Freebase'),
                    )
                );
            }
        }
    }

    protected function requestSpecificItem($id)
    {
        return $this->resourceOwner->requestVideo($id);
    }

    private function imageResolutions()
    {
        return array('default.jpg', 'mqdefault.jpg', 'hqdefault.jpg', 'sddefault.jpg', 'maxresdefault.jpg');
    }
}