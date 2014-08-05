<?php

namespace Controller;

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

        $fetcher = $app['api_consumer.fetcher'];

        try {
            $userSharedLinks = $fetcher->fetch($userId, $resource);

        } catch (\Exception $e) {
            return $app->json("An error occurred", 500);
        }

        return $app->json($userSharedLinks, 200);
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
