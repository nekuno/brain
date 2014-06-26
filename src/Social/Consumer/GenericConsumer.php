<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 6/26/14
 * Time: 1:30 PM
 */

namespace Social\Consumer;

use Guzzle\Http\Exception\RequestException;

class GenericConsumer extends ContainerAwareConsumer
{

    /**
     * Get users by resource owner
     *
     * @param $resource
     * @param $userId
     * @return mixed
     */
    protected function getUsersByResource($resource, $userId = null)
    {

        $sql = "SELECT * " .
            " FROM users AS u" .
            " INNER JOIN user_access_tokens AS ut ON u.id = ut.user_id" .
            " WHERE ut.resourceOwner = '" . $resource . "'";

        if (null !== $userId) {
            $sql .= " AND u.id = " . $userId;
        }

        $sql .= ";";

        try {
            $users = $this->app['db']->fetchAll($sql);
        } catch (\Exception $e) {
            throw new $e;
        }

        return $users;
    }

    /**
     * Fetch last links from user feed on Facebook
     *
     * @param $url
     * @return mixed
     * @throws RequestException
     */
    protected function fetchDataFromUrl($url)
    {
        $client = $this->app['guzzle.client'];

        $request = $client->get($url);

        try {
            $response = $request->send();
            $data     = $response->json();
        } catch (RequestException $e) {
            throw $e;
        }

        return $data;
    }

    protected function storeLinks(array $links)
    {

        $errors = 0;
        $result = array();

        foreach ($links as $link) {
            try {
                $model = $this->app['content.model'];
                $link  = $model->addLink($link);
                if ($link) {
                    $result[] = $link;
                }
            } catch (\Exception $e) {
                continue;
                $errors++;
            }
        }

        // TODO: Log and handle error percentage and make blocking if needed
        return $result;

    }

    /**
     * @param $e
     * @return string
     */
    protected function getError($e)
    {
        return sprintf('Error: %s on file %s line %s', $e->getMessage(), $e->getFile(), $e->getLine());
    }

} 