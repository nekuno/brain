<?php

namespace ApiConsumer\LinkProcessor\Processor;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\UrlParser;
use Http\OAuth\ResourceOwner\FacebookResourceOwner;
use Service\UserAggregator;

class FacebookProcessor extends AbstractProcessor
{
    const FACEBOOK_VIDEO = 'video';
    const FACEBOOK_OTHER = 'other';
    protected $FACEBOOK_VIDEO_TYPES = array('video_inline', 'video_autoplay');

    /**
     * @var $resourceOwner FacebookResourceOwner
     */
    protected $resourceOwner;

    /**
     * @param UserAggregator $userAggregator
     * @param FacebookResourceOwner $facebookResourceOwner
     * @param ScraperProcessor $scraperProcessor
     * @param UrlParser $urlParser
     */
    public function __construct(UserAggregator $userAggregator, ScraperProcessor $scraperProcessor, FacebookResourceOwner $facebookResourceOwner, UrlParser $urlParser)
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
        $type = $this->getAttachmentType($preprocessedLink);
        switch ($type) {
            case $this::FACEBOOK_VIDEO:
                $link = $this->processVideo($preprocessedLink);
                break;
            case $this::FACEBOOK_OTHER:
                $link = $this->scraperProcessor->process($preprocessedLink);
                break;
            default:
                return false;
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
        $link['title'] = null;
        $link['additionalLabels'] = array('Video');
        $link['additionalFields'] = array(
            'embed_type' => 'facebook',
            'embed_id' => $id);

        $link = array_merge($link, $this->scraperProcessor->process($preprocessedLink));

        $url = (string)$id;
        $query = array(
            'fields' => 'description,picture'
        );

        if ($preprocessedLink->getSource() == 'facebook') {
            $response = $this->resourceOwner->authorizedHTTPRequest($url, $query, $preprocessedLink->getToken());
        } else {
            $response = array();
        }

        $link['description'] = isset($response['description']) ? $response['description'] : null;
        $link['thumbnail'] = isset($response['picture']) ? $response['picture'] : null;


        return $link;
    }

    private function getAttachmentType(PreprocessedLink $preprocessedLink)
    {
        $link = $preprocessedLink->getLink();
        if (empty($link['types'])
        ) {
            return null;
        }
        //TODO: Check if there can be more than one attachment in one post
        if (in_array($link['types'][0], $this->FACEBOOK_VIDEO_TYPES)) {
            return $this::FACEBOOK_VIDEO;
        }

        return $this::FACEBOOK_OTHER;

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

}