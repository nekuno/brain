<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 6/17/14
 * Time: 6:39 PM
 */

namespace Controller;

use Silex\Application;
use Social\API\Consumer\Auth\DBUserProvider;
use Social\API\Consumer\Http\Client;
use Social\API\Consumer\Storage\DBStorage;
use Symfony\Component\HttpFoundation\Request;

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

    public function fetchLinksAction(Request $request, Application $app)
    {

        $userId = $request->get('userId');
        $resource = $request->get('resource');
        $FQNClassName = '\\Social\\API\\Consumer\\' . ucfirst($resource) . 'FeedConsumer';

        $storage = new DBStorage($app['content.model']);
        $userProvider = new DBUserProvider($app['db']);
        $httpClient = new Client($app['guzzle.client']);

        $consumer = new $FQNClassName($storage, $userProvider, $httpClient);;

        try {
            $result = $consumer->fetchLinks($userId);
        } catch(\Exception $e) {
            if($app['env'] == 'dev'){
                throw $e;
            }
            return $app->json(array(), 500);
        }

        return $app->json($result);

    }

}