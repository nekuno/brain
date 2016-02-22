<?php

namespace ApiConsumer\LinkProcessor\Processor;


use ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser;
use Http\OAuth\ResourceOwner\TwitterResourceOwner;
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

    public function __construct(UserAggregator $userAggregator, TwitterResourceOwner $resourceOwner, TwitterUrlParser $parser)
    {
        parent::__construct($userAggregator);
        $this->resourceOwner = $resourceOwner;
        $this->parser = $parser;
    }

    /**
     * @param $link
     * @return array|false Returns the processed link as array or false if this processor can not process the link
     */
    public function process(array $link)
    {
        $type = $this->parser->getUrlType($link['url']);

        switch ($type) {
            case TwitterUrlParser::TWITTER_INTENT:
                $link = $this->processIntent($link);
                break;
            case TwitterUrlParser::TWITTER_PROFILE:
                $link = $this->processProfile($link);
                break;
            default:
                return false;
                break;
        }

        return $link;
    }

    private function processIntent($link)
    {
        if (!isset($link['url'])) return false;

        $userId = isset($link['resourceItemId']) ?
            array('user_id' => $link['resourceItemId']) :
            $this->parser->getProfileIdFromIntentUrl($link['url']);

        $key = array_keys($userId)[0];
        $users = $this->resourceOwner->lookupUsersBy($key, array($userId[$key]));

        if (empty($users)) return false;

        return array_merge($link, $this->resourceOwner->buildProfileFromLookup($users[0]));
    }

    private function processProfile($link)
    {
        if (!isset($link['url'])) return false;

        $userName = $this->parser->getProfileNameFromProfileUrl($link['url']);

        $users = $this->resourceOwner->lookupUsersBy('screen_name', array($userName));
        if (empty($users)) return false;

        $this->addCreator($userName);

        return array_merge($link, $this->resourceOwner->buildProfileFromLookup($users[0]));
    }


}