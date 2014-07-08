<?php

namespace ApiConsumer\Restful\Consumer;

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

        $users = $this->userProvider->getUsersByResource('google', $userId);

        $links = array();

        foreach ($users as $user) {

            if (!$user['googleID']) {
                continue;
            }

            $url = 'https://www.googleapis.com/plus/v1/people/me/activities/public'
                . '?access_token=' . $user['oauthToken']
                . '&maxResults=100&fields=items(object(attachments(content,embed/url,url),content,url),title,url)';

            try {
                $response = $this->makeRequestJSON($url);
                $links[$userId] = $this->formatResponse($response);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        return $links;

    }

    protected function formatResponse(array $response = array())
    {
        $parsed = array();

        foreach ($response['items'] as $item) {
            if (!array_key_exists('object', $item) || !array_key_exists('attachments', $item['object'])) {
                continue;
            }
            $link['url']         = $item['object']['attachments'][0]['url'];
            $link['title']       = array_key_exists('title', $item)
                ? $item['title']
                : '';
            $link['description'] = array_key_exists('content', $item['object']['attachments'])
                ? $item['object']['attachments'][0]['content']
                : '';

            $parsed[] = $link;
        }

        return $parsed;
    }

}
