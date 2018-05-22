<?php

namespace ApiConsumer\LinkProcessor\Processor\SteamProcessor;

use ApiConsumer\Images\ProcessingImage;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\AbstractAPIProcessor;
use ApiConsumer\LinkProcessor\UrlParser\SteamUrlParser;
use ApiConsumer\ResourceOwner\SteamResourceOwner;

abstract class AbstractSteamProcessor extends AbstractAPIProcessor
{
    const STEAM_LABEL = 'LinkSteam';

    /** @var  SteamUrlParser */
    protected $parser;

    /** @var  SteamResourceOwner */
    protected $resourceOwner;

    public function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        $link = $preprocessedLink->getFirstLink();
        $link->addAdditionalLabels(self::STEAM_LABEL);
    }

    public function getImages(PreprocessedLink $preprocessedLink, array $data)
    {
        $defaultImageUrl = $this->brainBaseUrl . SteamUrlParser::DEFAULT_IMAGE_PATH;
        $defaultImage = $this->buildSquareImage($defaultImageUrl, ProcessingImage::LABEL_LARGE, 512);

        $firstLink = $preprocessedLink->getFirstLink();
        if ($thumbnail = $firstLink->getThumbnailLarge()) {
            try {
                $largeImage = $this->buildSquareImage($thumbnail, ProcessingImage::LABEL_LARGE, 512);
                $mediumImage = $this->buildSquareImage($thumbnail, ProcessingImage::LABEL_MEDIUM, 128);
                $smallImage = $this->buildSquareImage($thumbnail, ProcessingImage::LABEL_SMALL, 96);

                return array($largeImage, $mediumImage, $smallImage);
            } catch (\Exception $e) {}
        }

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