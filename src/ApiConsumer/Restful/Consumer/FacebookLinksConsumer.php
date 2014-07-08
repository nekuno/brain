<?php

namespace ApiConsumer\Restful\Consumer;

/**
 * Class FacebookLinksConsumer
 * @package Social
 */
class FacebookLinksConsumer extends AbstractLinksConsumer implements LinksConsumerInterface
{

    /**
     * { @inheritdoc }
     */
    public function fetchLinks($userId = null)
    {

        $users = $this->userProvider->getUsersByResource('facebook', $userId);

        $links = array();

        foreach ($users as $user) {

            if (!$user['facebookID']) {

                continue;

            }

            $url = 'https://graph.facebook.com/v2.0/' . $user['facebookID'] . '/links'
                . '?access_token=' . $user['oauthToken'];

            try {
                $response       = $this->makeRequestJSON($url);
                $links[$userId] = $this->formatResponse($response);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        return $links;

    }

    /**
     * { @inheritdoc }
     */
    protected function formatResponse(array $response = array())
    {
        $parsed = array();

        foreach ($response['data'] as $item) {
            $link['url']         = $item['link'];
            $link['title']       = array_key_exists('name', $item) ? $item['name'] : '';
            $link['description'] = array_key_exists('description', $item) ? $item['description'] : '';

            $parsed[] = $link;
        }

        return $parsed;
    }

}
