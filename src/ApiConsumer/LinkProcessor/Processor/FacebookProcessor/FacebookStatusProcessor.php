<?php

namespace ApiConsumer\LinkProcessor\Processor\FacebookProcessor;

use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\Images\ProcessingImage;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\FacebookUrlParser;

class FacebookStatusProcessor extends AbstractFacebookProcessor
{
    protected function requestItem(PreprocessedLink $preprocessedLink)
    {
        $id = $preprocessedLink->getResourceItemId();
        $token = $preprocessedLink->getToken();

        $item = $this->resourceOwner->requestStatus($id, $token);

        if (isset($item['privacy']['value']) && $item['privacy']['value'] !== 'EVERYONE') {
            throw new UrlNotValidException($preprocessedLink->getUrl(), sprintf('Url "%s" is not a public link', $preprocessedLink->getUrl()));
        }
    }

    public function getImages(PreprocessedLink $preprocessedLink, array $data)
    {
        $url = isset($data['full_picture']) ? $data['full_picture'] : $this->brainBaseUrl . FacebookUrlParser::DEFAULT_IMAGE_PATH;

        return array(new ProcessingImage($url));
    }
}