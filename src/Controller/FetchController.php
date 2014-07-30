<?php

namespace Controller;

use ApiConsumer\Auth\DBUserProvider;
use ApiConsumer\Registry\Registry;
use ApiConsumer\Restful\Consumer\ConsumerFactory;
use ApiConsumer\Storage\DBStorage;
use Model\LinkModel;
use Model\UserModel;
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
    public function addLinkAction(Request $request, Application $app)
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

        /** @var UserModel $userModel */
        $userModel = $app['users.model'];

        $hasUser = count($userModel->getById($userId)) > 0;
        if (false === $hasUser) {
            return $app->json('User not found', 404);
        }

        /** @var Logger $logger */
        $logger = $app['monolog'];

        $consumer = $this->getConsumer($app, $resource);
        $storage  = new DBStorage($app['links.model']);
        $registry = new Registry($app['orm.ems']['mysql_brain']);

        try {
            $logger->debug(sprintf('Fetch attempt for user %d, resource %s', $userId, $resource));
            $userSharedLinks = $consumer->fetchLinksFromUserFeed($userId);

            $storage->storeLinks($userId, $userSharedLinks);
            foreach ($storage->getErrors() as $error) {
                $logger->error(sprintf('Error saving link: ' . $error));
            }

            $numLinks = count($userSharedLinks);
            if ($numLinks) {
                $lastItemId = $userSharedLinks[$numLinks - 1]['resourceItemId'];
            } else {
                $lastItemId = null;
            }

            $registry->registerFetchAttempt(
                $userId,
                $resource,
                $lastItemId,
                false
            );

        } catch (\Exception $e) {
            $logger->error(sprintf('Error fetching from resource %s with message: %s', $resource, $e->getMessage()));

            $registry->registerFetchAttempt(
                $userId,
                $resource,
                null,
                true
            );

            return $app->json("An error occurred", 500);
        }

        return $app->json($userSharedLinks, 200);
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
        if (isset($app[$resource])) {
            $options = $app[$resource];
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
