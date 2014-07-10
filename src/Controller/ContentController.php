<?php

namespace Controller;

use ApiConsumer\Auth\DBUserProvider;
use ApiConsumer\Restful\Consumer\ConsumerFactory;
use ApiConsumer\Scraper\Scraper;
use ApiConsumer\Storage\DBStorage;
use Goutte\Client;
use Model\ContentModel;
use Silex\Application;
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

            $linksGroupedByUser = $consumer->fetchLinks($userId);

            $processedLinks = array();

            foreach ($linksGroupedByUser as $user => $userLinks) {
                $processedLinks[$user] = $this->scrapLinksMetadata($userLinks);
            }

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

    private function scrapLinksMetadata(array $links = array())
    {

        $processedLinks = array();

        foreach ($links as $link) {

            $metadata = $this->getMetadata($link);

            $metaTags = $metadata->getMetaTags();

            $metaOgData = $metadata->extractOgMetadata($metaTags);
            if (array() !== $metaOgData) {
                $link = $this->mergeLinkMetadata($metaOgData, $link);
            } else {
                $metaDefaultData = $metadata->extractDefaultMetadata($metaTags);
                if (array() !== $metaDefaultData) {
                    $link = $this->mergeLinkMetadata($metaDefaultData, $link);
                }
            }

            $processedLinks[] = $link;

        }

        return $processedLinks;

    }

    /**
     * @param $link
     * @return \ApiConsumer\Scraper\Metadata
     */
    private function getMetadata($link)
    {

        $scraper = new Scraper(new Client(), $link['url']);

        $metadata = $scraper->scrap();

        return $metadata;
    }

    /**
     * @param $scrapedData
     * @param $link
     * @return mixed
     */
    private function mergeLinkMetadata(array $scrapedData, array $link)
    {

        foreach ($scrapedData as $meta) {
            if (array_key_exists('title', $meta) && null !== $meta['title']) {
                $link['title'] = $meta['title'];
            }

            if (array_key_exists('description', $meta) && null !== $meta['description']) {
                $link['description'] = $meta['description'];
            }

            if (array_key_exists('canonical', $meta) && null !== $meta['canonical']) {
                $link['url'] = $meta['canonical'];
            }
        }

        return $link;

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
