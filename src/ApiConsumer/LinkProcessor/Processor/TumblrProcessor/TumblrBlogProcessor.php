<?php

namespace ApiConsumer\LinkProcessor\Processor\TumblrProcessor;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\TumblrUrlParser;
use Model\Link\Creator;

class TumblrBlogProcessor extends AbstractTumblrProcessor
{
    protected function requestItem(PreprocessedLink $preprocessedLink)
    {
        $token = $preprocessedLink->getToken();
        if (!$blogId = $preprocessedLink->getResourceItemId()) {
            $firstLink = $preprocessedLink->getFirstLink();
            $fixedUrl = TumblrUrlParser::fixUrl($firstLink->getUrl());
            $firstLink->setUrl($fixedUrl);
            $blogId = TumblrUrlParser::getBlogId($fixedUrl);
        }
        $response = $this->resourceOwner->requestBlog($blogId, $token);

        return isset($response['response']['blog']) ? $response['response']['blog'] : null;
    }

    public function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        parent::hydrateLink($preprocessedLink, $data);
        $link = $preprocessedLink->getFirstLink();
        $creator = Creator::buildFromLink($link);
        if (isset($data['title'])) {
            $creator->setTitle($data['title']);
            $creator->setDescription(null);
        }
        if (isset($data['description'])) {
            $creator->setDescription($data['description']);
        }

        $preprocessedLink->setFirstLink($creator);
    }
}