<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 6/9/14
 * Time: 3:12 PM
 */

namespace Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class UserController
{

    public function indexAction(Request $request, Application $app)
    {

        $model = $app['users.model'];
        $result = $model->getAll();
        $users = array();
        foreach ($result as $row) {
            $users[$row['u']->getProperty('username')]['username'] = $row['u']->getProperty('username');
            $users[$row['u']->getProperty('username')]['email'] = $row['u']->getProperty('email');
            $users[$row['u']->getProperty('username')]['qnoow_id'] = $row['u']->getProperty('qnoow_id');
        }

        return $app->json($users, 200);
    }

    public function addAction(Request $request, Application $app)
    {

        // Basic data validation
        if (array() !== $request->request->all()) {
            if (
                null == $request->request->get('id')
                || null == $request->request->get('username')
                || null == $request->request->get('email')
            ) {
                return $app->json(array(), 400);
            }

            if(!is_int($request->request->get('id')))
                return $app->json(array(), 400);
        } else {
            return $app->json(array(), 400);
        }

        // Create and persist the User
        $model = $app['users.model'];
        $result = $model->create($request->request->all());

        $user = array();

        foreach ($result as $row) {
            $user[$row['u']->getProperty('username')]['username'] = $row['u']->getProperty('username');
            $user[$row['u']->getProperty('username')]['email'] = $row['u']->getProperty('email');
            $user[$row['u']->getProperty('username')]['qnoow_id'] = $row['u']->getProperty('qnoow_id');
        }

        return $app->json($user, !empty($user) ? 201 : 200);

    }

    public function deleteAction(Request $request, Application $app)
    {

    }

    public function showAction(Request $request, Application $app)
    {

    }

} 