<?php

namespace ApiConsumer\Fetcher;

use Http\OAuth\ResourceOwner\TwitterResourceOwner;

class TwitterFollowingFetcher extends BasicPaginationFetcher
{
    protected $url = 'friends/ids.json';

    protected $paginationField = 'cursor';

    protected $pageLength = 5000;

    /**
     * @var TwitterResourceOwner
     */
    protected $resourceOwner;


    protected function getQuery()
    {

        return array(
            'count' => $this->pageLength,
        );

    }

    protected function getItemsFromResponse($response)
    {
        return $response['ids'];

    }

    protected function getPaginationIdFromResponse($response)
    {
        $paginationId = $response['next_cursor'];
        if ($paginationId == 0) {
            return null;
        }
        return $paginationId;
    }

    protected function parseLinks(array $rawFeed)
    {
        $links = $this->resourceOwner->lookupUsersBy('user_id', $rawFeed);

        if ($links == false || empty($links)) {
            foreach ($rawFeed as $id) {
                $links[] = array('url' => 'https://twitter.com/intent/user?user_id=' . $id,
                    'resourceItemId' => $id,
                    'title' => null,
                    'description' => null,
                    'timestamp' => 1000 * time(),
                    'resource' => $this->resourceOwner->getName());
            }
        } else {
            foreach ($links as &$link) {
                $screenName = $link['screen_name'];
                $link = $this->resourceOwner->buildProfileFromLookup($link);
                $link['processed'] = 1;
                $this->resourceOwner->dispatchChannel(array(
                    'url' => $link['url'],
                    'username' => $screenName,
                ));
            }
        }

        return $links;
    }


}