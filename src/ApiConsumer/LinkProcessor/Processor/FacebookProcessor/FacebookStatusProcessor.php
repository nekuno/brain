<?php

namespace ApiConsumer\LinkProcessor\Processor\FacebookProcessor;

use ApiConsumer\Images\ProcessingImage;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\FacebookUrlParser;

class FacebookStatusProcessor extends AbstractFacebookProcessor
{
    protected function requestItem(PreprocessedLink $preprocessedLink)
    {
        $id = $preprocessedLink->getResourceItemId();
        $token = $preprocessedLink->getToken();

        return $this->resourceOwner->requestStatus($id, $token);
    }

    public function getImages(PreprocessedLink $preprocessedLink, array $data)
    {
        $url = isset($data['full_picture']) ? $data['full_picture'] : $this->brainBaseUrl . FacebookUrlParser::DEFAULT_IMAGE_PATH;

        return array(new ProcessingImage($url));
    }
}