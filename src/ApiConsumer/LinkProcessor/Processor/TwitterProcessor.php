<?php

namespace ApiConsumer\LinkProcessor\Processor;


use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser;
use ApiConsumer\ResourceOwner\TwitterResourceOwner;
use Service\UserAggregator;

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

    public function __construct(UserAggregator $userAggregator, ScraperProcessor $scraperProcessor, TwitterResourceOwner $resourceOwner, TwitterUrlParser $parser)
    {
        parent::__construct($userAggregator, $scraperProcessor);
        $this->resourceOwner = $resourceOwner;
        $this->parser = $parser;
    }

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

    private function processIntent(PreprocessedLink $preprocessedLink)
    {
        $link = $preprocessedLink->getLink();

        $userId = isset($link['resourceItemId']) ?
            array('user_id' => $link['resourceItemId']) :
            $this->parser->getProfileIdFromIntentUrl($preprocessedLink->getCanonical());

        $key = array_keys($userId)[0];
        $users = $this->resourceOwner->lookupUsersBy($key, array($userId[$key]));

        if (empty($users)) return false;

        return array_merge($link, $this->resourceOwner->buildProfileFromLookup($users[0]));
    }

    private function processProfile(PreprocessedLink $preprocessedLink)
    {
        $link = $preprocessedLink->getLink();

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