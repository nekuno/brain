<?php

namespace ApiConsumer\LinkProcessor\Processor\TumblrProcessor;

use ApiConsumer\Exception\UrlNotValidException;
use ApiConsumer\LinkProcessor\PreprocessedLink;

class TumblrUnknownTypePostProcessor extends TumblrPostProcessor
{
    protected $type;

    protected function requestItem(PreprocessedLink $preprocessedLink)
    {
        $post = parent::requestItem($preprocessedLink);

        if (!isset($post['type'])) {
            $link = $preprocessedLink->getFirstLink();
            throw new UrlNotValidException($link->getUrl());
        }

        $this->setType($post['type']);

        return $post;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        switch ($this->type) {
            case 'audio':
                $this->hydrateAudioLink($preprocessedLink, $data);
                break;
            case 'video':
                $this->hydrateVideoLink($preprocessedLink, $data);
                break;
            case 'photo':
                $this->hydratePhotoLink($preprocessedLink, $data);
                break;
            case 'link':
                $this->hydrateLinkLink($preprocessedLink, $data);
                break;
            default:
                $link = $preprocessedLink->getFirstLink();
                throw new UrlNotValidException($link->getUrl());
        }
    }

    public function getImages(PreprocessedLink $preprocessedLink, array $data)
    {
        switch ($this->type) {
            case 'audio':
                return $this->getAudioImages($preprocessedLink, $data);
            case 'video':
                return parent::getImages($preprocessedLink, $data);
            case 'photo':
                return $this->getPhotoImages($preprocessedLink, $data);
            case 'link':
                return $this->getLinkImages($preprocessedLink, $data);
            default:
                $link = $preprocessedLink->getFirstLink();
                throw new UrlNotValidException($link->getUrl());
        }
    }
}