<?php

namespace Controller;

use Model\ContentModel;
use Silex\Application;
use Social\API\Consumer\AbstractConsumer;
use Social\API\Consumer\Auth\DBUserProvider;
use Social\API\Consumer\LinksConsumerInterface;
use Social\API\Consumer\Storage\DBStorage;
use Symfony\Component\HttpFoundation\Request;

class ContentController
{

    public function addLink(Request $request, Application $app)
    {

        $data = $request->request->all();

        try {
            /** @var ContentModel $model */
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

        $userId   = $request->query->get('userId');
        $resource = $request->query->get('resource');

        if (null === $userId || null === $resource) {
            return $app->json(array(), 400);
        }

        $FQNClassName = 'Social\\API\\Consumer\\' . ucfirst($resource) . 'Consumer';

        $storage      = new DBStorage($app['content.model']);
        $userProvider = new DBUserProvider($app['db']);
        $httpClient   = $app['guzzle.client'];

        $options = array();

        if ($resource == 'twitter') {
            $options = array(
                'oauth_consumer_key'     => $app['twitter.consumer_key'],
                'oauth_consumer_secret' => $app['twitter.consumer_secret'],
            );
        }

        $consumer = new $FQNClassName($storage, $userProvider, $httpClient, $options);

        try {
            $result = $consumer->fetchLinks($userId);
        } catch (\Exception $e) {
            return $app->json(array(), 500);
        }
        
        return $app->json($result);

    }

}
