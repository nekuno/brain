<?php

namespace ApiConsumer\LinkProcessor\Processor;


use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser;
use ApiConsumer\ResourceOwner\TwitterResourceOwner;
use Model\User\TokensModel;

class TwitterProcessor extends AbstractProcessor
{
	/**
	 * @var TwitterResourceOwner
     */
	protected $resourceOwner;

	/**
	 * @var TwitterUrlParser
	 */
	protected $parser;

    /**
     * @inheritdoc
     */
    public function process(PreprocessedLink $preprocessedLink)
    {
        if ($this->parser->getUrlType($preprocessedLink->getFetched()) === TwitterUrlParser::TWITTER_IMAGE) {
            //$preprocessedLink->addAdditionalLabel('Image');
            $link = array_merge($preprocessedLink->getLink(), $this->scraperProcessor->process($preprocessedLink));
            return $link;
        } else {
            $type = $this->parser->getUrlType($preprocessedLink->getCanonical());

            $link = $preprocessedLink->getLink();
            $link['url'] = $preprocessedLink->getCanonical();
            $preprocessedLink->setLink($link);

            switch ($type) {
                case TwitterUrlParser::TWITTER_INTENT:
                    $link = $this->processIntent($preprocessedLink);
                    break;
                case TwitterUrlParser::TWITTER_PROFILE:
                    $link = $this->processProfile($preprocessedLink);
                    break;
                case TwitterUrlParser::TWITTER_TWEET:
                    $link = $this->processTweet($preprocessedLink);
                    break;
                default:
                    $link = $this->scraperProcessor->process($preprocessedLink);
                    break;
            }
        };

        return $link;
    }

    /**
     * @param $preprocessedLinks PreprocessedLink[]
     * @return array|bool
     */
    public function processMultipleProfiles($preprocessedLinks){

        $userNames = array();
        foreach ($preprocessedLinks as $key=>$preprocessedLink){

            $link = $preprocessedLink->getLink();

            if ($preprocessedLink->getSource() == TokensModel::TWITTER) {

                if (isset($link['title']) && !empty($link['title'])
                    && isset($link['description']) && !empty($link['description'])
                    && isset($link['url']) && !empty($link['url'])
                    && isset($link['thumbnail'])
                    && !(isset($link['process']) && $link['process'] == 0)
                ) {
                    unset($preprocessedLinks[$key]);
                }
            }

            $userName = $this->parser->getProfileNameFromProfileUrl($preprocessedLink->getCanonical());
            $this->addCreator($userName);
            $userNames[] = $userName;
        }

        $users = $this->resourceOwner->lookupUsersBy('screen_name', $userNames);

        if (empty($users)) return false;

        $links = array();
        foreach ($users as $user){
            $links[] = $this->resourceOwner->buildProfileFromLookup($user);
        }

        return $links;
    }

    /**
     * @param $preprocessedLinks PreprocessedLink[]
     * @return array|bool
     */
    public function processMultipleIntents($preprocessedLinks){

        $userIds = array('user_id' => array(), 'screen_name' => array());
        foreach ($preprocessedLinks as $key=>$preprocessedLink){

            $link = $preprocessedLink->getLink();

            if ($preprocessedLink->getSource() == TokensModel::TWITTER) {

                if (isset($link['title']) && !empty($link['title'])
                    && isset($link['description']) && !empty($link['description'])
                    && isset($link['url']) && !empty($link['url'])
                    && isset($link['thumbnail'])
                    && !(isset($link['process']) && $link['process'] == 0)
                ) {
                    unset($preprocessedLinks[$key]);
                }
            }

            $userId = isset($link['resourceItemId']) ?
                array('user_id' => $link['resourceItemId']) :
                $this->parser->getProfileIdFromIntentUrl($preprocessedLink->getCanonical());

            $key = array_keys($userId)[0];
            $userIds[$key][] = $userId;
        }

        $users = array();
        foreach ($userIds as $key => $ids) {
            $users = array_merge($this->resourceOwner->lookupUsersBy($key, $ids));
        }

        if (empty($users)) return false;

        $links = array();
        foreach ($users as $user){
            $links[] = $this->resourceOwner->buildProfileFromLookup($user);
        }

        return $links;
    }

    private function processIntent(PreprocessedLink $preprocessedLink)
    {
        $link = $preprocessedLink->getLink();

        if ($preprocessedLink->getSource() == TokensModel::TWITTER) {

            if (isset($link['title']) && !empty($link['title'])
                && isset($link['description']) && !empty($link['description'])
                && isset($link['url']) && !empty($link['url'])
                && isset($link['thumbnail'])
                && !(isset($link['process']) && $link['process'] == 0)
            ) {
                return $link;
            }
        }

        $userId = isset($link['resourceItemId']) ?
            array('user_id' => $link['resourceItemId']) :
            $this->parser->getProfileIdFromIntentUrl($preprocessedLink->getCanonical());

        $key = array_keys($userId)[0];

        $token = $preprocessedLink->getSource() == TokensModel::TWITTER ? $preprocessedLink->getToken() : array();

        $users = $this->resourceOwner->lookupUsersBy($key, array($userId[$key]), $token);

        if (empty($users)) return false;

        return array_merge($link, $this->resourceOwner->buildProfileFromLookup($users[0]));
    }

    private function processProfile(PreprocessedLink $preprocessedLink)
    {
        $link = $preprocessedLink->getLink();

        if ($preprocessedLink->getSource() == TokensModel::TWITTER) {

            if (isset($link['title']) && !empty($link['title'])
                && isset($link['description']) && !empty($link['description'])
                && isset($link['url']) && !empty($link['url'])
                && isset($link['thumbnail'])
                && !(isset($link['process']) && $link['process'] == 0)
            ) {
                return $link;
            }
        }

        $userName = $this->parser->getProfileNameFromProfileUrl($preprocessedLink->getCanonical());

        $users = $this->resourceOwner->lookupUsersBy('screen_name', array($userName));
        if (empty($users)) return false;

        $this->addCreator($userName);

        return array_merge($link, $this->resourceOwner->buildProfileFromLookup($users[0]));
    }

    private function processTweet(PreprocessedLink $preprocessedLink)
    {
        $statusId = $this->parser->getStatusIdFromTweetUrl($preprocessedLink->getCanonical());

        $url = $this->processTweetStatus($statusId);

        if ($url) {
            $preprocessedLink->setCanonical($url);
        }

        return $this->scraperProcessor->process($preprocessedLink);

    }

    /**
     * Follow embedded tweets (like from retweets) until last url
     * @param $statusId
     * @param $counter int Avoid infinite loops and some "joke" tweet chains
     * @return string|bool
     */
    private function processTweetStatus($statusId, $counter = 0)
    {
        if ($counter >= 10) {
            return false;
        }

        $query = array('id' => (int)$statusId);
        $apiResponse = $this->resourceOwner->authorizedAPIRequest('statuses/show.json', $query);

        $link = $this->extractLinkFromResponse($apiResponse);

        if (isset($link['id'])) {
            return $this->processTweetStatus($link['id'], ++$counter);
        }

        if (isset($link['url'])) {
            return $link['url'];
        }

        return false;
    }

    private function extractLinkFromResponse($apiResponse)
    {
        //if tweet quotes another
        if (isset($apiResponse['quoted_status_id'])) {
            //if tweet is main, API returns quoted_status
            if (isset($apiResponse['quoted_status'])) {

                return $this->extractLinkFromResponse($apiResponse['quoted_status']);

            } else if (isset($apiResponse['is_quote_status']) && $apiResponse['is_quote_status'] == true) {
                return array('id' => $apiResponse['quoted_status_id']);
            } else {
                //should not be able to enter here
            }
        }

        //if tweet includes url or media in text
        if (isset($apiResponse['entities'])) {
            $entities = $apiResponse['entities'];

            $media = $this->getEntityUrl($entities, 'media');
            if ($media) {
                return $media;
            }

            $url = $this->getEntityUrl($entities, 'urls');
            if ($url) {
                return $url;
            }
        }
        //we do not want tweets with no content
        return false;
    }

    private function getEntityUrl($entities, $name)
    {
        if (isset($entities[$name]) && !empty($entities[$name])) {
            $urlObject = $entities[$name][0]; //TODO: Foreach
            return array('url' => $urlObject['display_url']);
        }

        return false;
    }

}