<?php

namespace Controller;

use Silex\Application;

/**
 * Class ClientController
 *
 * @package Controller
 */
class ClientController
{
    public function versionAction(Application $app)
    {
        $client = $app['api_consumer.link_processor.goutte'];
        $crawler = $client->request('GET', 'https://play.google.com/store/apps/details?id=com.nekuno');

        $dateDiv = $crawler->filter('#wrapper div.content[itemprop = datePublished]');
        return $app->json($dateDiv->text(), 200);
    }
}
