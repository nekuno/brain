<?php

namespace Controller;

use ApiConsumer\Auth\DBUserProvider;
use ApiConsumer\History\Registry;
use ApiConsumer\Restful\Consumer\ConsumerFactory;
use ApiConsumer\Scraper\Scraper;
use ApiConsumer\Storage\DBStorage;
use ApiConsumer\TempFakeService;
use Goutte\Client;
use Model\LinkModel;
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
            $model = $app['links.model'];
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

        $userId = $request->query->get('userId');
        $resource = $request->query->get('resource');

        if (null === $userId || null === $resource) {
            return $app->json(array(), 400);
        }

        $userModel = $app['users.model'];

        if (count($userModel->getById($userId)) === 0) {
            return $app->json('User not found', 404);
        }

        $storage = new DBStorage($app['links.model']);
        $consumer = $this->getConsumer($app, $resource);

        try {
            $linksGroupByUser = $consumer->fetchLinks($userId);

            $registry = new Registry($app['orm.ems']['mysql_brain']);
            $registry->recordFetchAttempt($userId, $resource);
        } catch (\Exception $e) {
            $app['monolog']->addError(sprintf('Error fetching links for user %d from resource %s', $userId, $resource));

            return $app->json($this->getError($e), 500);
        }

        try {
            $scraper = new Scraper(new Client());
            $tempFakeService = new TempFakeService($scraper);

            $processedLinks = $tempFakeService->processLinks($linksGroupByUser);

            $storage->storeLinks($processedLinks);

            $errors = $storage->getErrors();
            if (array() !== $errors) {
                foreach ($errors as $error) {
                    $app['monolog']->addError($error);
                }
            }
        } catch (\Exception $e) {
            return $app->json($this->getError($e), 500);
        }

        return $app->json($processedLinks);
    }

    /**
     * @param Application $app
     * @param $resource
     * @return \ApiConsumer\Restful\Consumer\LinksConsumerInterface
     * @throws \Exception
     */
    private function getConsumer(Application $app, $resource)
    {

        $userProvider = new DBUserProvider($app['dbs']['mysql_social']);
        $httpClient = $app['guzzle.client'];

        $options = array();

        if ($resource == 'twitter') {
            $options = array(
                'oauth_consumer_key' => $app['twitter.consumer_key'],
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
