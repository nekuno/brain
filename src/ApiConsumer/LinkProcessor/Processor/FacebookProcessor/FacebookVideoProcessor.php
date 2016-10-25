<?php

namespace ApiConsumer\LinkProcessor\Processor\FacebookProcessor;

use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use Model\User\TokensModel;
use Model\Video;

class FacebookVideoProcessor extends AbstractFacebookProcessor
{
    function requestItem(PreprocessedLink $preprocessedLink)
    {
        $id = $this->getItemId($preprocessedLink);
        $preprocessedLink->setResourceItemId($id);

        if ($preprocessedLink->getSource() == TokensModel::FACEBOOK) {
            $response = $this->resourceOwner->requestVideo($id, $preprocessedLink->getToken());
        } else {
           $response = array();
        }

        return $response;
    }

    function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        $video = new Video();
        $video->setDescription(isset($data['description']) ? $data['description'] : null);
        $video->setThumbnail(isset($data['picture']) ? $data['picture'] : null);
        $video->setTitle($this->buildTitleFromDescription($data));
        $video->setEmbedType(TokensModel::FACEBOOK);
        $video->setEmbedId($preprocessedLink->getResourceItemId());

        $preprocessedLink->setLink($video);
    }

    protected function getItemIdFromParser($url)
    {
        return $this->parser->getVideoId($url);
    }

}