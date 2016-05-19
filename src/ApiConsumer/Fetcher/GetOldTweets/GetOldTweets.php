<?php


namespace ApiConsumer\Fetcher\GetOldTweets;

use ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser;
use Model\Tweet;
use Model\TweetCriteria;
use Model\User\TokensModel;

class GetOldTweets
{
    const MAX_TWEETS = 1000;
    const MAX_LINKS = 500;

    protected $parser;
    protected $tweetManager;

    public function __construct(TwitterUrlParser $parser, TweetManager $tweetManager)
    {
        $this->parser = $parser;
        $this->tweetManager = $tweetManager;
    }

    public function fetchFromUser($username)
    {
        $minDate = null;
        $links = array();
        do{
            $until = $minDate;
            $tweets = $this->fetchTweets($username, GetOldTweets::MAX_TWEETS, null, $until);
            if (!empty($tweets)){
                $links = array_merge($links, $this->getLinksFromTweets($tweets));
                $minDate = $this->getMinDate($tweets);
            }

        } while ($this->needMore($links) && ($until !== $minDate));

        return $links;
    }

    /**
     * @param $username
     * @param int $maxtweets
     * @param null $since
     * @param null $until
     * @param null $querysearch
     * @return \Model\Tweet[]
     */
    public function fetchTweets($username, $maxtweets = GetOldTweets::MAX_TWEETS, $since = null, $until = null, $querysearch = null)
    {
        $criteria = new TweetCriteria();
        $criteria->setUsername($username);
        $criteria->setMaxTweets($maxtweets);
        $criteria->setSince($since);
        $criteria->setUntil($until);
        $criteria->setQuerySearch($querysearch);

        return $this->tweetManager->getTweets($criteria);
    }

    public function getDateString(Tweet $tweet)
    {
        /* @var $date \DateTime */
        $date = $tweet->getDate();
        return $date->format('Y-m-d');
    }

    /**
     * @param Tweet [] $tweets
     * @return array
     */
    private function getLinksFromTweets(array $tweets)
    {
        $links = array();
        $resource = TokensModel::TWITTER;

        foreach ($tweets as $tweet) {
            $text = utf8_encode($tweet->getText());

            $newUrls = $this->parser->extractURLsFromText($text);
            $timestamp = $this->getTimestamp($tweet);

            $errorCharacters = array('&Acirc;', '&acirc;', '&brvbar;', '&nbsp;');
            foreach ($newUrls as $newUrl) {

                $newUrl = str_replace($errorCharacters, '', htmlentities($newUrl));
                $newUrl = html_entity_decode($newUrl);
                $links[] = array(
                    'url' => $newUrl,
                    'timestamp' => $timestamp,
                    'resource' => $resource);
            }
        }
        return $links;
    }

    private function needMore(array $links)
    {
        if (count($links) <= $this::MAX_LINKS) {
            return true;
        }
        return false;
    }

    private function getMinDate(array $tweets)
    {
        return min(array_map(array($this, 'getDateString'), $tweets));
    }

    private function getTimestamp(Tweet $tweet)
    {
        /* @var $date \DateTime */
        $date = $tweet->getDate();
        return $date->getTimestamp();
    }
}