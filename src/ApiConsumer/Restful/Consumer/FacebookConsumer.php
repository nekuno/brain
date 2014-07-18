<?php

namespace ApiConsumer\Restful\Consumer;

/**
 * Class FacebookConsumer
 *
 * @package Social
 */
class FacebookConsumer extends AbstractConsumer implements LinksConsumerInterface
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
                . '?access_token=' . $user['oauthToken'] . '&limit=20';

            try {
                $response       = $this->makeRequestJSON($url);
                $links[$user['id']] = $this->formatResponse($response);
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
            $link['title']       = array_key_exists('name', $item) ? $item['name'] : null;
            $link['description'] = array_key_exists('description', $item) ? $item['description'] : null;
            $link['resourceItemId'] = array_key_exists('id', $item) ? (int) $item['id'] : null;
            $link['resource'] = 'facebook';

            $parsed[] = $link;
        }

        return $parsed;
    }
}
