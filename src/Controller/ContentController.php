<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 6/17/14
 * Time: 6:39 PM
 */

namespace Controller;

use Guzzle\Http\Exception\RequestException;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentController
{

    public function addLink(Request $request, Application $app)
    {

        $data = $request->request->all();

        try {
            $model  = $app['content.model'];
            $result = $model->addLink($data);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json($result, empty($result) ? 200 : 201);

    }

    public function fetchFacebookLinksAction(Request $request, Application $app)
    {

        $user = $request->get('user');

        $userData = $this->getUserData($app['db'], $user, 'facebook');

        if (empty($userData)) {
            return new Response('User not found', 404);
        }

        $client = $app['guzzle.client'];

        $request = $client->get('https://graph.facebook.com/v2.0/' . $userData['facebook_id'] . '/links?access_token=' . $userData['access_token']);

        try {
            $response = $request->send();
            $data     = $response->json();
        } catch (RequestException $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        $result = array();

        foreach ($data['data'] as $item) {
            $link['url']   = $item['link'];
            $link['title'] = array_key_exists('name', $item) ? $item['name'] : '';;
            $link['description'] = array_key_exists('description', $item) ? $item['description'] : '';
            $link['userId']      = $user;

            try {
                $model    = $app['content.model'];
                $link = $model->addLink($link);
                if($link){
                    $result[] = $link;
                }
            } catch (\Exception $e) {
                if ($app['env'] == 'dev') {
                    throw $e;
                }
                return $app->json(array(), 500);
            }

        }

        return $app->json($result);

    }

    /**
     * @param $db
     * @param $user
     * @param $resource
     * @return mixed
     */
    protected function getUserData($db, $user, $resource)
    {

        $property = $resource . 'ID';

        $sql = "
          SELECT
              u.id AS user_id,
              u." . $property . " AS " . $resource . "_id,
              ut.oauthToken AS access_token,
              ut.expireTime AS token_expiration_time,
              ut.resourceOwner AS resource_owner
          FROM
              users AS u
          INNER JOIN
              user_access_tokens AS ut
          ON
              u.id = ut.user_id
          WHERE
              user_id = :id
          AND
              ut.resourceOwner = '" . $resource . "'
          LIMIT 1
        ";

        try {
            $userData = $db->fetchAssoc($sql, array('id' => $user));
        } catch (\Exception $e) {
            if (getenv('APP_ENV') == 'dev') {
                throw $e;
            }
            throw new \Exception('Error on DB Query');
        }

        return $userData;

    }

}