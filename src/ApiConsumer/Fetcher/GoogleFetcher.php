<?php

namespace ApiConsumer\Fetcher;

class GoogleFetcher extends BasicPaginationFetcher
{
    protected $paginationField = 'pageToken';

    protected $pageLength = 20;

    protected $paginationId = null;

    public function setUser($user){
        parent::setUser($user);
        if (!array_key_exists('googleID', $this->user)){
            $this->user['googleID'] = $this->resourceOwner->getUsername($this->user);
        }
    }

    public function getUrl()
    {
        return 'plus/v1/people/' . $this->user['googleID'] . '/activities/public';
    }

    protected function getQuery()
    {
        return array(
            'maxResults' => $this->pageLength,
            'fields' => 'items(object(attachments(content,displayName,id,objectType,url)),title,published,updated),nextPageToken'
        );
    }

    protected function getItemsFromResponse($response)
    {
        return $response['items'] ?: array();
    }

    protected function getPaginationIdFromResponse($response)
    {

        $paginationId = null;

        if (isset($response['nextPageToken'])) {
            $paginationId = $response['nextPageToken'];
        }

        if ($this->paginationId === $paginationId) {
            return null;
        }

        $this->paginationId = $paginationId;

        return $paginationId;
    }

    /**
     * @param $rawFeed array
     * @return array
     */
    protected function parseLinks(array $rawFeed)
    {
        $parsed = array();

        foreach ($rawFeed as $item) {
            if (!isset($item['object']['attachments'][0]['url'])) {
                continue;
            }

            $timestamp = null;
            if (array_key_exists('updated', $item)) {
                $date = new \DateTime($item['updated']);
                $timestamp = ($date->getTimestamp()) * 1000;
            } else if (array_key_exists('published', $item)) {
                $date = new \DateTime($item['published']);
                $timestamp = $date->getTimestamp() * 1000;
            }

            $item = $item['object']['attachments'][0];

            $link['url'] = $item['url'];
            $link['title'] = array_key_exists('displayName', $item) ? $item['displayName'] : null;
            $link['description'] = array_key_exists('content', $item) ? $item['content'] : null;
            $link['resourceItemId'] = array_key_exists('id', $item) ? $item['id'] : null;
            $link['timestamp'] = $timestamp;
            $link['resource'] = 'google';

            $parsed[] = $link;
        }

        return $parsed;
    }
}
