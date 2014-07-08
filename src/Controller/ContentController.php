<?php

namespace Controller;

use ApiConsumer\Auth\DBUserProvider;
use ApiConsumer\Restful\Consumer\ConsumerFactory;
use ApiConsumer\Storage\DBStorage;
use ApiConsumer\WebScraper\Scraper;
use Goutte\Client;
use Model\ContentModel;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

        $storage      = new DBStorage($app['content.model']);
        $userProvider = new DBUserProvider($app['db']);
        $httpClient   = $app['guzzle.client'];

        $options = array();

        if ($resource == 'twitter') {
            $options = array(
                'oauth_consumer_key'    => $app['twitter.consumer_key'],
                'oauth_consumer_secret' => $app['twitter.consumer_secret'],
            );
        }

        $consumer = ConsumerFactory::create($resource, $userProvider, $httpClient, $options);

        try {

            $links = $consumer->fetchLinks($userId);

            $storage->storeLinks($links);

            $errors = $storage->getErrors();

            if (array() !== $errors) {
                $app->json($errors, 500);
            }

        } catch (\Exception $e) {
            return $app->json($this->getError($e), 500);
        }

        return $app->json($links);

    }

}

    /**
     * @param $e
     * @return string
     */
    protected function getError(\Exception $e)
    {
        return sprintf('Error: %s on file %s line %s', $e->getMessage(), $e->getFile(), $e->getLine());
    }
}
