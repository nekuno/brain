<?php

namespace ApiConsumer\LinkProcessor\Processor;


use ApiConsumer\LinkProcessor\PreprocessedLink;
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
     * @inheritdoc
     */
    public function process(PreprocessedLink $link)
    {
        //TODO: If getUrlType(fetched) == image, processImage
        $type = $this->parser->getUrlType($link->getCanonical());

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

    private function processIntent(PreprocessedLink $preprocessedLink)
    {
        if (!$preprocessedLink->getCanonical()) return false;

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
        if (!$preprocessedLink->getCanonical()) return false;

        $link = $preprocessedLink->getLink();

        $userName = $this->parser->getProfileNameFromProfileUrl($preprocessedLink->getCanonical());

        $users = $this->resourceOwner->lookupUsersBy('screen_name', array($userName));
        if (empty($users)) return false;

        $this->addCreator($userName);

        return array_merge($link, $this->resourceOwner->buildProfileFromLookup($users[0]));
    }


}