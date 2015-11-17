<?php
/**
 * Created by PhpStorm.
 * User: yawmoght
 * Date: 27/10/15
 * Time: 13:30
 */

namespace ApiConsumer\LinkProcessor\Processor;


use ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser;
use Http\OAuth\ResourceOwner\TwitterResourceOwner;

class TwitterProcessor implements ProcessorInterface
{

    /**
     * @var TwitterResourceOwner
     */
    protected $resourceOwner;

    /**
     * @var TwitterUrlParser
     */
    protected $parser;

    public function __construct(TwitterResourceOwner $resourceOwner, TwitterUrlParser $parser)
    {
        $this->resourceOwner = $resourceOwner;
        $this->parser = $parser;
    }

    /**
     * @param $link
     * @return array|false Returns the processed link as array or false if the processer can not process the link
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

        $userId = isset($link['resourceItemId']) ? $link['resourceItemId'] : $this->parser->getProfileIdFromUrl($link['url']);

        $users = $this->resourceOwner->lookupUsersBy('user_id', array($userId));

        if (empty($users)) return false;

        return array_merge($link, $this->resourceOwner->buildProfileFromLookup($users[0]));
    }

    private function processProfile($link)
    {
        if (!isset($link['url'])) return false;

        $userName = $this->parser->getProfileNameFromUrl($link['url']);

        $users = $this->resourceOwner->lookupUsersBy('screen_name', array($userName));

        if (empty($users)) return false;

        return array_merge($link, $this->resourceOwner->buildProfileFromLookup($users[0]));
    }


}