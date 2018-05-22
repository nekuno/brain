<?php

namespace ApiConsumer\LinkProcessor\Processor\YoutubeProcessor;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\Processor\AbstractAPIProcessor;
use ApiConsumer\LinkProcessor\UrlParser\YoutubeUrlParser;
use ApiConsumer\ResourceOwner\GoogleResourceOwner;
use Model\Token\Token;

abstract class AbstractYoutubeProcessor extends AbstractAPIProcessor
{
    const YOUTUBE_LABEL = 'LinkYoutube';

    /**
     * @var YoutubeUrlParser
     */
    protected $parser;

    /**
     * @var GoogleResourceOwner
     */
    protected $resourceOwner;

    protected $itemApiUrl;
    protected $itemApiParts;

    protected function requestItem(PreprocessedLink $preprocessedLink)
    {
        $itemId = $this->getItemId($preprocessedLink->getUrl());
        $preprocessedLink->setResourceItemId(reset($itemId));
        $token = $preprocessedLink->getToken();

        $response = $this->requestSpecificItem($itemId, $token);

        return $response;
    }

    protected function isValidResponse(array $response)
    {
        return isset($response['items']) && is_array($response['items']) && count($response['items']) > 0 && isset($response['items'][0]['snippet']);
    }

    function hydrateLink(PreprocessedLink $preprocessedLink, array $data)
    {
        $link = $preprocessedLink->getFirstLink();

        $link->addAdditionalLabels(self::YOUTUBE_LABEL);
        $snippet = $data['items'][0]['snippet'];
        $link->setTitle($snippet['title']);
        $link->setDescription($snippet['description']);
    }

    abstract protected function requestSpecificItem($id, Token $token = null);

}