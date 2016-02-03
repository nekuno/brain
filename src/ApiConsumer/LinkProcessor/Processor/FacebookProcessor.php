<?php

namespace ApiConsumer\LinkProcessor\Processor;

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
     * @var $scrapperProcessor ScraperProcessor
     */
    protected $scraperProcessor;

    /**
     * @param UserAggregator $userAggregator
     * @param FacebookResourceOwner $facebookResourceOwner
     * @param ScraperProcessor $scraperProcessor
     */
    public function __construct(UserAggregator $userAggregator, FacebookResourceOwner $facebookResourceOwner, ScraperProcessor $scraperProcessor)
    {
        parent::__construct($userAggregator);
        $this->resourceOwner = $facebookResourceOwner;
        $this->scraperProcessor = $scraperProcessor;
    }

    /**
     * @param array $link
     * @return array
     */
    public function process(array $link)
    {
        $type = $this->getAttachmentType($link);
        switch ($type) {
            case $this::FACEBOOK_VIDEO:
                $link = $this->processVideo($link);
                break;
            case $this::FACEBOOK_OTHER:
                $link = $this->scraperProcessor->process($link);
                break;
            default:
                return false;
                break;
        }

        return $link;
    }

    protected function processVideo($link)
    {
        $id = $this->getVideoIdFromURL($link['url']);

        $link['title'] = null;
        $link['additionalLabels'] = array('Video');
        $link['additionalFields'] = array(
            'embed_type' => 'facebook',
            'embed_id' => $id);

        $link = $this->scraperProcessor->process($link);

        $url = (string)$id;
        $query = array(
            'fields' => 'description,picture'
        );

        $token = array();
        if (isset($link['resourceOwnerToken'])) {
            $token = $link['resourceOwnerToken'];
        }
        $response = $this->resourceOwner->authorizedHTTPRequest($url, $query, $token);
        $link['description'] = isset($response['description']) ? $response['description'] : null;
        $link['thumbnail'] = isset($response['picture']) ? $response['picture'] : null;


        return $link;
    }

    private function getAttachmentType($link)
    {
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