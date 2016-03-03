<?php

namespace ApiConsumer\LinkProcessor\Processor;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\FacebookUrlParser;
use Http\OAuth\ResourceOwner\FacebookResourceOwner;
use Model\User\TokensModel;
use Service\UserAggregator;

class FacebookProcessor extends AbstractProcessor
{
    const FACEBOOK_VIDEO = 'video';
    protected $FACEBOOK_VIDEO_TYPES = array('video_inline', 'video_autoplay');

    /**
     * @var $resourceOwner FacebookResourceOwner
     */
    protected $resourceOwner;

    /**
     * @param UserAggregator $userAggregator
     * @param ScraperProcessor $scraperProcessor
     * @param FacebookResourceOwner $facebookResourceOwner
     * @param FacebookUrlParser $urlParser
     */
    public function __construct(UserAggregator $userAggregator, ScraperProcessor $scraperProcessor, FacebookResourceOwner $facebookResourceOwner, FacebookUrlParser $urlParser)
    {
        parent::__construct($userAggregator, $scraperProcessor);
        $this->resourceOwner = $facebookResourceOwner;
        $this->scraperProcessor = $scraperProcessor;
        $this->parser = $urlParser;
    }

    /**
     * @inheritdoc
     */
    public function process(PreprocessedLink $preprocessedLink)
    {
        $type = $this->getUrlType($preprocessedLink);
        switch ($type) {
            case $this::FACEBOOK_VIDEO:
                $link = $this->processVideo($preprocessedLink);
                break;
            case FacebookUrlParser::FACEBOOK_PAGE:
                $link = $this->processPage($preprocessedLink);
                break;
            default:
                $link = $this->scraperProcessor->process($preprocessedLink);
                $link['processed'] = 0;
                break;
        }

        $link['url'] = $preprocessedLink->getCanonical();

        return $link;
    }

    /**
     * @param $preprocessedLink PreprocessedLink
     * @return array
     */
    protected function processVideo($preprocessedLink)
    {
        $id = $this->getVideoIdFromURL($preprocessedLink->getFetched());

        $link = array();
        $link['additionalLabels'] = array('Video');
        $link['additionalFields'] = array(
            'embed_type' => TokensModel::FACEBOOK,
            'embed_id' => $id);

        $url = (string)$id;
        $query = array(
            'fields' => 'description,picture'
        );

        if ($preprocessedLink->getSource() == TokensModel::FACEBOOK) {
            $response = $this->resourceOwner->authorizedHTTPRequest($url, $query, $preprocessedLink->getToken());
        } else {
            $response = array();
        }

        $link['description'] = isset($response['description']) ? $response['description'] : null;
        $link['thumbnail'] = isset($response['picture']) ? $response['picture'] : null;
        $link['title'] = $this->buildTitleFromDescription($link['description']);

        return $link;
    }

    /**
     * @param $preprocessedLink PreprocessedLink
     * @return array
     */
    protected function processPage($preprocessedLink)
    {
        if (isset($preprocessedLink->getLink()['pageId'])) {
            $url = $preprocessedLink->getLink()['pageId'];
            $query = array(
                'fields' => 'name,description,picture'
            );

            if ($preprocessedLink->getSource() == TokensModel::FACEBOOK) {
                $response = $this->resourceOwner->authorizedHTTPRequest($url, $query, $preprocessedLink->getToken());
            } else {
                $response = $this->resourceOwner->authorizedAPIRequest($url, $query);
            }

            $link = array(
                'description' => isset($response['description']) ? $response['description'] : null,
                'title' => isset($response['name']) ? $response['name'] : $this->buildTitleFromDescription($response['description']),
                'thumbnail' => isset($response['picture']) ? $response['picture'] : null,
            );

            //TODO: Get better picture from api

        } else {
//TODO: Try to process as profile
            $link = array
            (
                'title' => '',
                'description' => '',
                'processed' => 0,
            );
        }

        return $link;

    }

    private function getUrlType(PreprocessedLink $preprocessedLink)
    {
        $link = $preprocessedLink->getLink();

        //TODO: Check if there can be more than one attachment in one post
        if (isset($link['types']) && isset($link['types'][0]) && in_array($link['types'][0], $this->FACEBOOK_VIDEO_TYPES)) {
            return $this::FACEBOOK_VIDEO;
        }

        return $this->parser->getUrlType($preprocessedLink->getCanonical());
    }

    private function getVideoIdFromURL($url)
    {
        $prefix = 'videos/';
        $startPos = strpos($url, $prefix);
        if ($startPos === false) {
            return null;
        }
        return substr($url, $startPos + strlen($prefix));

    }

    private function buildTitleFromDescription($description)
    {
        return strlen($description) >= 25 ? substr($description, 0, 22) . '...' : $description;
    }

}