<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 6/17/14
 * Time: 6:39 PM
 */

namespace Controller;

use Silex\Application;
use Social\Consumer\FacebookFeedConsumer;
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

    public function fetchFacebookLinksAction(Request $request, Application $app)
    {

        $consumer = new FacebookFeedConsumer($app);

        $userId = $request->get('userId');

        try {
            $result = $consumer->fetch($userId);
        } catch(\Exception $e) {
            if($app['env'] == 'dev'){
                throw $e;
            }
            return $app->json(array(), 500);
        }

        return $app->json($result);

    }

}