<?php

namespace ApiConsumer\LinkProcessor\Processor;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\FacebookUrlParser;
use ApiConsumer\ResourceOwner\FacebookResourceOwner;
use Model\User\TokensModel;

/**
 * Class FacebookProcessor
 * @package ApiConsumer\LinkProcessor\Processor
 * @method FacebookUrlParser getParser
 */
class FacebookProcessor extends AbstractProcessor
{
    const FACEBOOK_VIDEO = 'video';
    protected $FACEBOOK_VIDEO_TYPES = array('video_inline', 'video_autoplay');

	/**
	 * @var $resourceOwner FacebookResourceOwner
	 */
	protected $resourceOwner;

	/**
	 * @var FacebookUrlParser
	 */
	protected $parser;

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
            case FacebookUrlParser::FACEBOOK_PROFILE:
                $link = $this->processProfile($preprocessedLink);
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
        $link['title'] = $this->buildTitleFromDescription($link);

        return $link;
    }

    /**
     * @param $preprocessedLink PreprocessedLink
     * @return array
     */
    protected function processProfile($preprocessedLink)
    {
        //TODO: When Facebook App Token is implemented, include option to request public if source != facebook
        if ($preprocessedLink->getSource() == TokensModel::FACEBOOK
            && (isset($preprocessedLink->getLink()['pageId']) || isset($preprocessedLink->getLink()['resourceItemId']))
        ) {

            if (isset($preprocessedLink->getLink()['pageId'])) {
                $id = $preprocessedLink->getLink()['pageId'];
                $fields = array('name', 'description', 'picture');
            } else {
                $id = $preprocessedLink->getLink()['resourceItemId'];
                $fields = array('name', 'picture');

                if ($this->getParser()->isStatusId($id)){
                    $fields[] = 'description';
                }
            }

            $query = array('fields' => implode(',', $fields));

            $response = $this->resourceOwner->authorizedHTTPRequest($id, $query, $preprocessedLink->getToken());

            $thumbnail = $this->resourceOwner->getPicture($id, $preprocessedLink->getToken());
            $link = array(
                'description' => isset($response['description']) ? $response['description'] : $this->buildDescriptionFromTitle($response),
                'title' => isset($response['name']) ? $response['name'] : $this->buildTitleFromDescription($response),
                'thumbnail' => is_string($thumbnail) ? $thumbnail : (isset($response['picture']) ? $response['picture'] : null),
            );

        } else {
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

    private function buildTitleFromDescription(array $response)
    {
        if (!isset($response['description'])){
            return null;
        }
        $description = $response['description'];

        return strlen($description) >= 25 ? substr($description, 0, 22) . '...' : $description;
    }

    private function buildDescriptionFromTitle(array $response)
    {
        return isset($response['name'])? $response['name'] : null;
    }

}