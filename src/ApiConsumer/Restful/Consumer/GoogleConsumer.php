<?php

namespace ApiConsumer\Restful\Consumer;

/**
 * Class GoogleConsumer
 *
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

        foreach ($users as $user) {

            if (!$user['googleID']) {
                continue;
            }

            $url = 'https://www.googleapis.com/plus/v1/people/me/activities/public'
                . '?access_token=' . $user['oauthToken']
                . '&maxResults=20'
                . '&fields=items(object(attachments(content,displayName,id,objectType,url)),title),nextPageToken';

            try {
                $response = $this->makeRequestJSON($url);

                $links[$user['id']] = $this->formatResponse($response);

                return $links;
            } catch (\Exception $e) {
                throw $e;
            }
        }

    }

    protected function formatResponse(array $response = array())
    {

        $parsed = array();

        foreach ($response['items'] as $item) {
            if (!array_key_exists('object', $item) || !array_key_exists('attachments', $item['object'])) {
                continue;
            }

            $item = $item['object']['attachments'][0];

            $link['url']            = $item['url'];
            $link['title']          = array_key_exists('displayName', $item) ? $item['displayName'] : null;
            $link['description']    = array_key_exists('content', $item) ? $item['content'] : null;
            $link['resourceItemId'] = array_key_exists('id', $item) ? $item['id'] : null;
            $link['resource']       = 'google';

            $parsed[] = $link;
        }

        return $parsed;
    }
}
