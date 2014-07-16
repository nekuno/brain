<?php

namespace Controller;

use ApiConsumer\Auth\DBUserProvider;
use ApiConsumer\Restful\Consumer\ConsumerFactory;
use ApiConsumer\Scraper\Metadata;
use ApiConsumer\Scraper\Scraper;
use ApiConsumer\Storage\DBStorage;
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
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
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
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function fetchLinksAction(Request $request, Application $app)
    {

        $userId   = $request->query->get('userId');
        $resource = $request->query->get('resource');

        if (null === $userId || null === $resource) {
            return $app->json(array(), 400);
        }

        $storage = new DBStorage($app['links.model']);

        $consumer = $this->getConsumer($app, $resource);

        try {
            $linksGroupedByUser = $consumer->fetchLinks($userId);

            $processedLinks = $this->processLinks($linksGroupedByUser);

            $storage->storeLinks($processedLinks);

            $errors = $storage->getErrors();
            if (array() !== $errors) {
                foreach ($errors as $error) {
                    $app->json($errors, 500); // TODO: this is only for development
                    $app['monolog']->addDebug($error);
                }
            }
        } catch (\Exception $e) {
            return $app->json($this->getError($e), 500);
        }

        return $app->json($processedLinks);
    }

    /**
     * @param $linksGroupedByUser
     * @return array
     */
    private function processLinks(array $linksGroupedByUser = array())
    {

        $processedLinks = array();

        foreach ($linksGroupedByUser as $user => $userLinks) {

            $userProcessedLinks = $this->processUserLinks($userLinks);

            $processedLinks[$user] = $userProcessedLinks;
        }

        return $processedLinks;
    }

    /**
     * @param $userLinks
     * @return array
     */
    private function processUserLinks($userLinks)
    {

        $processedLinks = array();

        foreach ($userLinks as $link) {

            $metadata = $this->getMetadata($link['url']);

            $metaTags = $metadata->getMetaTags();

            $metaOgData = $metadata->extractOgMetadata($metaTags);

            if (array() !== $metaOgData) {
                $link = $metadata->mergeLinkMetadata($metaOgData, $link);
            } else {
                $metaDefaultData = $metadata->extractDefaultMetadata($metaTags);
                if (array() !== $metaDefaultData) {
                    $link = $metadata->mergeLinkMetadata($metaDefaultData, $link);
                }
            }

            $processedLinks[] = $link;
        }

        return $processedLinks;
    }

    /**
     * @param $url
     * @throws \Exception
     * @return \ApiConsumer\Scraper\Metadata
     */
    private function getMetadata($url)
    {

        $goutte = new Client();

        try {
            $scraper = new Scraper($goutte);
            $crawler = $scraper->initCrawler($url)->scrap('//meta | //title');

            return new Metadata($crawler);
        } catch (\Exception $e) {
            throw $e;
        }
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
