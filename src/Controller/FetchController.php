<?php

namespace Controller;

use ApiConsumer\Auth\DBUserProvider;
use ApiConsumer\Registry\Registry;
use ApiConsumer\Restful\Consumer\ConsumerFactory;
use ApiConsumer\Storage\DBStorage;
use Model\LinkModel;
use Monolog\Logger;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class FetchController
 *
 * @package Controller
 */
class FetchController
{

    /**
     * Add link action
     */
    public function addLink(Request $request, Application $app)
    {

        $data = $request->request->all();

        try {
            /** @var LinkModel $model */
            $model  = $app['links.model'];
            $result = $model->addLink($data);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json($result, empty($result) ? 200 : 201);
    }

    /**
     * Fetch links action. if user
     */
    public function fetchLinksAction(Request $request, Application $app)
    {

        $userId   = $request->query->get('userId');
        $resource = $request->query->get('resource');

        if (null === $userId || null === $resource) {
            return $app->json(array(), 400);
        }

        $userModel = $app['users.model'];

        if (count($userModel->getById($userId)) === 0) {
            return $app->json('User not found', 404);
        }

        /** @var $logger Logger */
        $logger        = $app['monolog'];
        $entityManager = $app['orm.ems']['mysql_brain'];
        $registry      = new Registry($entityManager);
        $storage       = new DBStorage($app['links.model']);

        $consumer = $this->getConsumer($app, $resource);

        $linksGroupByUser = array();

        try {
            $linksGroupByUser = $consumer->fetchLinks($userId);

            $registry->registerFetchAttempt(
                $userId,
                $resource,
                $linksGroupByUser[$userId],
                false
            );

        } catch (\Exception $e) {

            $registry->registerFetchAttempt(
                $userId,
                $resource,
                $linksGroupByUser[$userId],
                true
            );

            return $app->json(array(), 500);
        }

        $storage->storeLinks($linksGroupByUser);

        $errors = $storage->getErrors();
        if (array() !== $errors) {
            foreach ($errors as $error) {
                $logger->addDebug($error);
            }
        }

        return $app->json($linksGroupByUser, 200);
    }

    /**
     * @param Application $app
     * @param $resource
     * @return \ApiConsumer\Restful\Consumer\LinksConsumerInterface
     */
    private function getConsumer(Application $app, $resource)
    {

        $userProvider = new DBUserProvider($app['dbs']['mysql_social']);
        $httpClient   = $app['guzzle.client'];

        $options = array();

        if ($resource == 'twitter') {
            $options = array(
                'oauth_consumer_key'    => $app['twitter.consumer_key'],
                'oauth_consumer_secret' => $app['twitter.consumer_secret'],
            );
        }

        return ConsumerFactory::create($resource, $userProvider, $httpClient, $options);
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