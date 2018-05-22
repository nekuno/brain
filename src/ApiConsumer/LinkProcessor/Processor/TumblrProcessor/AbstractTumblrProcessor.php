<?php

namespace ApiConsumer\LinkProcessor\Processor\TumblrProcessor;

use ApiConsumer\Images\ProcessingImage;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\AbstractAPIProcessor;
use ApiConsumer\LinkProcessor\UrlParser\TumblrUrlParser;
use ApiConsumer\ResourceOwner\TumblrResourceOwner;

abstract class AbstractTumblrProcessor extends AbstractAPIProcessor
{
    const TUMBLR_LABEL = 'LinkTumblr';

    /** @var  TumblrUrlParser */
    protected $parser;

    /** @var  TumblrResourceOwner */
    protected $resourceOwner;

    public function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        $link = $preprocessedLink->getFirstLink();
        $link->addAdditionalLabels(self::TUMBLR_LABEL);
    }

    public function getImages(PreprocessedLink $preprocessedLink, array $data)
    {
        $defaultImageUrl = $this->brainBaseUrl . TumblrUrlParser::DEFAULT_IMAGE_PATH;
        $defaultImage = $this->buildSquareImage($defaultImageUrl, ProcessingImage::LABEL_LARGE, 512);

        if (!$blogId = $preprocessedLink->getResourceItemId()) {
            $firstLink = $preprocessedLink->getFirstLink();
            $blogId = TumblrUrlParser::getBlogId($firstLink->getUrl());
        }
        try {
            $largeThumbnailUrl = $this->resourceOwner->requestBlogAvatar($blogId, 512, $preprocessedLink->getToken());
            $largeImage = $this->buildSquareImage($largeThumbnailUrl, ProcessingImage::LABEL_LARGE, 512);

            $mediumThumbnailUrl = $this->resourceOwner->requestBlogAvatar($blogId, 128, $preprocessedLink->getToken());
            $mediumImage = $this->buildSquareImage($mediumThumbnailUrl, ProcessingImage::LABEL_MEDIUM, 128);

            $smallThumbnailUrl = $this->resourceOwner->requestBlogAvatar($blogId, 96, $preprocessedLink->getToken());
            $smallImage = $this->buildSquareImage($smallThumbnailUrl, ProcessingImage::LABEL_SMALL, 96);

            return array($largeImage, $mediumImage, $smallImage);
        } catch (\Exception $e) {}

        return array($defaultImage);
    }

    protected function buildSquareImage($url, $label, $size = null)
    {
        $image = new ProcessingImage($url);
        $image->setLabel($label);
        $image->setWidth($size);
        $image->setHeight($size);

        return $image;
    }
}