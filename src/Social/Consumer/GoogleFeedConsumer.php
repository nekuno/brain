<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 6/26/14
 * Time: 1:25 PM
 */

namespace Social\Consumer;

class GoogleFeedConsumer extends GenericConsumer
{

    /**
     * Fetch data for all users if $userId is null
     *
     * @param null $userId
     * @return array
     */
    public function fetchLinks($userId = null)
    {

        $errors = array();

        $users = $this->getUsersByResource('google', $userId);

        $data = array();
        foreach ($users as $user) {

            $url = 'https://www.googleapis.com/plus/v1/people/me/activities/public'
                . '?access_token=' . $user['oauthToken']
                . '&maxResults=100&fields=items(object(attachments(content,embed/url,url),content,url),title,url)';

            try {
                $data[$user['id']] = $this->fetchDataFromUrl($url, $user['oauthToken']);
            } catch (\Exception $e) {
                $errors[] = $this->getError($e);
            }

        }

        $links = array();
        foreach ($data as $userId => $shared) {
            try {
                $parseLinks = $this->parseLinks($shared, $userId);
                $links = $links + $parseLinks;
            } catch (\Exception $e) {
                $errors[] = $this->getError($e);

            }
        }

        $stored = array();
        try {
            $stored = $this->storeLinks($links);
        } catch (\Exception $e) {
            $errors[] = $this->getError($e);
        }

        return empty($errors) ? $stored : $errors;

    }

    /**
     * Save links to Graph DB
     *
     * @param $data fetched data from user feed
     * @param $userId
     * @return array
     * @throws \Exception
     */
    protected function parseLinks($data, $userId)
    {
        $parsed = array();

        foreach ($data['items'] as $item) {
            if(!array_key_exists('object', $item) || !array_key_exists('attachments', $item['object'])){
                continue;
            }
            $link['url']   = $item['object']['attachments'][0]['url'];
            $link['title'] = array_key_exists('title', $item) ? $item['title'] : '';
            $link['description'] = array_key_exists('content', $item['object']['attachments']) ? $item['object']['attachments'][0]['content'] : '';
            $link['userId']      = $userId;

            $parsed[] = $link;
        }

        return $parsed;
    }

}