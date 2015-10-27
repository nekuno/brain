<?php

namespace ApiConsumer\Fetcher;

class TwitterFetcher extends BasicPaginationFetcher
{
    protected $url = 'statuses/user_timeline.json';

    protected $paginationField = 'since_id';

    protected $pageLength = 200;

    protected $mode;

    const TWITTER_FETCHING_LINKS='links';
    const TWITTER_FETCHING_FOLLOWING='following';

    /**
     * { @inheritdoc }
     */
    public function fetchLinksFromUserFeed($user, $public)
    {
        $this->setUser($user);
        $this->rawFeed = array();

        $this->mode = $this::TWITTER_FETCHING_LINKS;
        $rawFeed = $this->getLinksByPage($public);
        $links = $this->parseLinks($rawFeed);

        $this->rawFeed=array();
        $this->mode = $this::TWITTER_FETCHING_FOLLOWING;
        $rawFeed = $this->getLinksByPage($public);
        $following = $this->parseFollowing($rawFeed);
        $links = array_merge($links, $following);
        return $links;
    }

    protected function getQuery()
    {
        if ($this->mode == $this::TWITTER_FETCHING_FOLLOWING){
            return array(
                'count' => 5000,
            );
        }

        return array(
            'count' => $this->pageLength,
            'trim_user' => 'true',
            'exclude_replies' => 'true',
            'contributor_details' => 'false',
            'include_rts' => 'false',
        );
    }

    protected function getItemsFromResponse($response)
    {
        if ($this->mode == $this::TWITTER_FETCHING_FOLLOWING)
        {
            return $response['ids'];
        }
        return $response;
    }

    protected function getPaginationIdFromResponse($response)
    {
        if ($this->mode == $this::TWITTER_FETCHING_FOLLOWING){
            $nextCursor = $response['next_cursor'];
            if ($nextCursor==0){
                return null;
            }
        }

        $paginationId = null;

        $itemsCount = count($response);
        if ($itemsCount > 0 && $itemsCount > $this->pageLength) {
            $lastItem = $response[count($response) - 1];
            $paginationId = $lastItem['id_str'];
        } else {
            return null;
        }

        return $paginationId;
    }

    /**
     * @param $rawFeed array
     * @return array
     */
    protected function parseLinks(array $rawFeed)
    {
        $formatted = array();

        foreach ($rawFeed as $item) {
            if (empty($item['entities']) || empty($item['entities']['urls'][0])) {
                continue;
            }

            $url = $item['entities']['urls'][0]['expanded_url']
                ? $item['entities']['urls'][0]['expanded_url']
                : $item['entities']['urls'][0]['url'];

            $timestamp = null;
            if (array_key_exists('created_at', $item)) {
                $date = new \DateTime($item['created_at']);
                $timestamp = ($date->getTimestamp()) * 1000;
            }

            $link = array();
            $link['url'] = $url;
            $link['title'] = array_key_exists('text', $item) ? $item['text'] : null;
            $link['description'] = null;
            $link['resourceItemId'] = array_key_exists('id', $item) ? $item['id'] : null;
            $link['timestamp'] = $timestamp;
            $link['resource'] = 'twitter';

            $formatted[] = $link;
        }

        return $formatted;
    }

    private function parseFollowing($rawFollowing)
    {
        $links = array();
        foreach($rawFollowing as $id){
            $links[] = array('url' => 'https://twitter.com/intent/user?user_id='.$id,
                'resourceItemId' => $id,
                'title' => null,
                'description' => null,
                'timestamp' => 1000*time(),
                'resource' => 'twitter');
        }
        return $links;
    }

    protected function getPaginationField()
    {
        if ($this->mode==$this::TWITTER_FETCHING_FOLLOWING){
            return 'cursor';
        }

        return parent::getPaginationField();
    }

    public function getUrl()
    {
        if ($this->mode== $this::TWITTER_FETCHING_FOLLOWING){
            return 'friends/ids.json';
        }
        return parent::getUrl();
    }


}