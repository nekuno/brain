<?php

namespace ApiConsumer\Restful\Consumer;

use Social\API\Consumer\LinksConsumerInterface;

/**
 * Class GoogleConsumer
 * @package Social\API\Consumer
 */
class GoogleConsumer extends AbstractConsumer implements LinksConsumerInterface
{

    /**
     * { @inheritdoc }
     */
    public function fetchLinks($userId = null)
    {

        $errors = array();

        $users = $this->userProvider->getUsersByResource('google', $userId);

        $data = array();

        foreach ($users as $user) {

            if (!$user['googleID']) {
                continue;
            }

            $url = 'https://www.googleapis.com/plus/v1/people/me/activities/public'
                . '?access_token=' . $user['oauthToken']
                . '&maxResults=100&fields=items(object(attachments(content,embed/url,url),content,url),title,url)';

            try {
                $data[$user['id']] = $this->fetch($url);
            } catch (\Exception $e) {
                $errors[] = $this->getError($e);
            }

        }

        $stored = array();

        try {
            $stored = $this->processData($data);
        } catch (\Exception $e) {
            $errors[] = $this->getError($e);
        }

        return array() !== $errors ? $errors : $stored;

    }

    /**
     * { @inheritdoc }
     */
    protected function parseLinks($userId, array $data = array())
    {
        $parsed = array();

        foreach ($data['items'] as $item) {
            if (!array_key_exists('object', $item) || !array_key_exists('attachments', $item['object'])) {
                continue;
            }
            $link['url']         = $item['object']['attachments'][0]['url'];
            $link['title']       = array_key_exists('title', $item) ? $item['title'] : '';
            $link['description'] = array_key_exists('content', $item['object']['attachments'])
                ? $item['object']['attachments'][0]['content'] : '';
            $link['userId']      = $userId;

            $parsed[] = $link;
        }

        return $parsed;
    }

}
