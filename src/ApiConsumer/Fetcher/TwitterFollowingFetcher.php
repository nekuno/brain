<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\LinkProcessor\PreprocessedLink;
use ApiConsumer\ResourceOwner\TwitterResourceOwner;

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
        return isset($response['ids']) ? $response['ids'] : array();

    }

    protected function getPaginationIdFromResponse($response)
    {
        $paginationId = isset($response['next_cursor']) ? $response['next_cursor'] : null;
        if ($paginationId == 0) {
            return null;
        }
        return $paginationId;
    }

    //TODO: Refactor to use RO->processMultipleProfiles
    /**
     * @inheritdoc
     */
    protected function parseLinks(array $rawFeed)
    {
        $links = $this->resourceOwner->lookupUsersBy('user_id', $rawFeed);

        $preprocessedLinks = array();
        if ($links == false || empty($links)) {
            foreach ($rawFeed as $id) {
                $link = array('url' => 'https://twitter.com/intent/user?user_id=' . $id,
                    'resourceItemId' => $id,
                    'title' => null,
                    'description' => null,
                    'timestamp' => 1000 * time(),
                    'resource' => $this->resourceOwner->getName());
                $preprocessedLink = new PreprocessedLink($link['url']);
                $preprocessedLink->setLink($link);
                $preprocessedLinks[] = $preprocessedLink;
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
                $preprocessedLink = new PreprocessedLink($link['url']);
                $preprocessedLink->setLink($link);
                $preprocessedLinks[] = $preprocessedLink;
            }
        }

        return $preprocessedLinks;
    }


}