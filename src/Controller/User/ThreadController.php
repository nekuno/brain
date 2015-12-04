<?php
/**
 * @author Roberto M. Pallarola <yawmoght@gmail.com>
 */

namespace Controller\User;


use GuzzleHttp\Message\Request;
use Silex\Application;

class ThreadController
{

    public function getAction(Application $app, $id)
    {
        //threadmanager -> getbyuser(id)

        //return array(Thread1, Thread2..)
    }

    public function postAction(Application $app, Request $request)
    {
        //separate filters by type

        //create thread (profileFilters, userFilters)

        //return result
    }

    public function putAction(Application $app, Request $request)
    {
        //separate filters by type

        //update thread (profileFilters, userFilters)

        //return result
    }

    public function deleteAction (Application $app, $id)
    {
        //delete thread (id)

        //return result
    }

    public function getUsersAction (Application $app, $id)
    {

        //threadManager -> getUsers(id) (calls userRecomendationPaginatedModel inside)

        //return result (already paginated)
    }

    public function getContentAction (Application $app, $id)
    {

        //threadManager -> getContent(id) (calls contentRecomendationPaginatedModel inside)

        //return result
    }
}