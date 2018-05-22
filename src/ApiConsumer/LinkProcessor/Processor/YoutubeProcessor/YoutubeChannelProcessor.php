<?php

namespace ApiConsumer\LinkProcessor\Processor\YoutubeProcessor;

use ApiConsumer\Images\ProcessingImage;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser;
use Model\Token\Token;

class YoutubeChannelProcessor extends AbstractYoutubeProcessor
{
    function getItemIdFromParser($url)
    {
        return $this->parser->getChannelId($url);
    }

    function addTags(PreprocessedLink $preprocessedLink, array $item)
    {
        $link = $preprocessedLink->getFirstLink();

        if (isset($item['brandingSettings']['channel']['keywords'])) {
            $tags = $item['brandingSettings']['channel']['keywords'];
            preg_match_all('/".*?"|\w+/', $tags, $results);
            if ($results) {
                foreach ($results[0] as $tagName) {
                    $link->addTag(
                        array(
                            'name' => $tagName,
                        )
                    );
                }
            }
        }
    }

    protected function requestSpecificItem($id, Token $token = null)
    {
        return $this->resourceOwner->requestChannel($id, $token);
    }

    public function getImages(PreprocessedLink $preprocessedLink, array $data)
    {
        $default = $this->brainBaseUrl . YoutubeUrlParser::DEFAULT_IMAGE_PATH;

        $largeUrlSize = 'high';
        $largeThumbnailUrl = $this->getProfileUrl($data, $largeUrlSize, $default);
        $largeImage = $this->buildSquareImage($largeThumbnailUrl, ProcessingImage::LABEL_LARGE);

        $mediumUrlSize = 'medium';
        $mediumThumbnailUrl = $this->getProfileUrl($data, $mediumUrlSize, $default);
        $mediumImage = $this->buildSquareImage($mediumThumbnailUrl, ProcessingImage::LABEL_MEDIUM);

        $smallUrlSize = 'default';
        $smallThumbnailUrl = $this->getProfileUrl($data, $smallUrlSize, $default);
        $smallImage = $this->buildSquareImage($smallThumbnailUrl, ProcessingImage::LABEL_SMALL);

        return array($smallImage, $mediumImage, $largeImage);
    }

    protected function getProfileUrl($data, $size, $default)
    {
        return isset($data['snippet']['thumbnails']) && isset($data['snippet']['thumbnails'][$size]) ?
            $data['snippet']['thumbnails'][$size]['url'] : $default;
    }

    protected function buildSquareImage($url, $label)
    {
        $size = $this->getThumbnailSize($url);
        $image = new ProcessingImage($url);
        $image->setWidth($size);
        $image->setHeight($size);
        $image->setLabel($label);

        return $image;
    }

    public function getThumbnailSize($thumbnailUrl)
    {
        $parts = explode('/', $thumbnailUrl);
        $length = count($parts);
        $sizePart = $parts[$length - 2];

        if (substr($sizePart, 0, 1) !== 's'){
            return null;
        }

        $hyphenPosition = strpos($sizePart, '-');
        $length = $hyphenPosition - 1;
        $size = substr($sizePart, 1, $length);

        return (integer)$size;
    }

}