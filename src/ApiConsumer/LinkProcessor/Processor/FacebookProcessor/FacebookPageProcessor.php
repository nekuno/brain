<?php

namespace ApiConsumer\LinkProcessor\Processor\FacebookProcessor;

use ApiConsumer\Exception\UrlChangedException;
use ApiConsumer\Images\ProcessingImage;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\FacebookUrlParser;
use Model\Link\Creator;

class FacebookPageProcessor extends AbstractFacebookProcessor
{
    protected function requestItem(PreprocessedLink $preprocessedLink)
    {
        $id = $preprocessedLink->getResourceItemId() ?: $this->parser->getUsername($preprocessedLink->getUrl());
        $token = $preprocessedLink->getToken();

        $response = $this->resourceOwner->requestPage($id, $token);

        if ($this->isProfileResponse($response)) {
            $preprocessedLink->setType(FacebookUrlParser::FACEBOOK_PROFILE);
            throw new UrlChangedException($preprocessedLink->getUrl(), $preprocessedLink->getUrl(), 'Facebook page identified as profile');
        }

        return $response;
    }

    public function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        $link = $preprocessedLink->getFirstLink();
        $creator = Creator::buildFromLink($link);
        $preprocessedLink->setFirstLink($creator);

        parent::hydrateLink($preprocessedLink, $data);
    }

    protected function isProfileResponse(array $response)
    {
        return isset($response['error']) && isset($response['error']['code']) && $response['error']['code'] == 803;
    }

    public function getImages(PreprocessedLink $preprocessedLink, array $data)
    {
        if (isset($data['full_picture']) && !isset($data['full_picture']['data'])) {
            return array(new ProcessingImage($data['full_picture']));
        }
        if (isset($data['picture']) && !isset($data['picture']['data'])) {
            return array(new ProcessingImage($data['picture']));
        }


        $images = parent::getImages($preprocessedLink, $data);

        $imagesArray = isset($data['images']) && is_array($data['images']) ? $data['images'] : array();

        foreach ($imagesArray as $imageArray) {
            $image = new ProcessingImage($imageArray['source']);
            $image->setHeight($imageArray['height']);
            $image->setWidth($imageArray['width']);
            $images[] = $image;
        }

        return $images;
    }

}