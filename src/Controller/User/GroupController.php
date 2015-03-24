<?php

namespace Controller\User;

use Model\User\GroupModel;
use Model\UserModel;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class GroupController
 * @package Controller
 */
class GroupController
{

    /**
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */

     public function addAction(Request $request, Application $app)
    {

        // Basic data validation
        if (array() !== $request->request->all()) {
            if (null == $request->request->get('groupName')
            ) {
                return $app->json(array(), 400);
            }
        } else {
            return $app->json(array(), 400);
        }

        // Create and persist the Group

        try {
            $model = $app['users.groups.model'];
            $isCreated=$model->isAlreadyCreated($request->request->get('groupName'));
            if ($isCreated){
                $result=array();
            } else {
                $result = $model->create($request->request->get('groupName'));
            }
            
            $result["wasCreated"]=$isCreated;
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json($result, !empty($result) ? 201 : 200);
    }

    /**
     * This method accepts requests with 'groupId' or 'groupName' fields
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
     public function showAction(Request $request, Application $app)
    {

        if (null === $request->get('groupId') && null === $request->get('groupName')) {
            return $app->json(array(), 404);
        }
      
        if ((null!==$request->get('groupId'))&&(is_int($request->get('groupId')))){
            try {
                $model = $app['users.groups.model'];
                $result = $model->getById($request->get('groupId'));
            } catch (\Exception $e) {
                if ($app['env'] == 'dev') {
                    throw $e;
                }
                return $app->json(array(), 500);
            }

            return $app->json($result, !empty($result) ? 200 : 404);
        }
        
        if (null!==$request->get('groupName')){

            try {
                $model = $app['users.groups.model'];
                $result = $model->getByName($request->get('groupName'));
            } catch (\Exception $e) {
                if ($app['env'] == 'dev') {
                    throw $e;
                }
                return $app->json(array(), 500);
            }
            return $app->json($result, !empty($result) ? 200 : 404);
        }
        
        return $app->json(array(), 200);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function deleteAction(Request $request, Application $app)
    {

        if (null === $request->get('groupName')) {
            return $app->json(array(), 400);
        }


        try {
            $model = $app['users.groups.model'];

            $isCreated = $model->isAlreadyCreated($request->get('groupName'));
            if ($isCreated){
                $model->remove($request->get('groupName'));
            }

            return  $app->json(array("wasCreated"=>$isCreated), 200);
            
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json(array(), 200);
    }

    /**
     * Creates a "Belonging to" relationship between user and group
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function addUserAction(Request $request, Application $app)
    {

        // Basic data validation
        if (array() !== $request->request->all()) {
            if     (null == $request->get('groupName') 
                || (null == $request->request->get('id'))
            ) {
                return $app->json(array('reason'=>'null value',
                                        'id'=>$request->request->get('id'),
                                        'groupName'=>$request->get('groupName')
                                        ), 400);
            }

            //comment this validation (and add cast to int) if
            //debugging with Postman 1.02, due to bug 831 (need to send key->value)
            if (!is_int($request->request->get('id'))) {

                    return $app->json(  array('reason'=>'id not int',
                                              'id'    =>$request->request->get('id')),
                                        400);
            }

        } else {
            return $app->json(array('reason'=>'null values'), 400);
        }

        try {
            $model = $app['users.groups.model'];
            $isBelonging=$model->isUserFromGroup($request->get('groupName'),
                                                $request->request->get('id'));
            if (!$isBelonging){
                $model->addUserToGroup(array('id'=>$request->request->get('id'),
                                             'groupName'=>$request->get('groupName')));
            }

        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json(array('wasBelonging'=>$isBelonging), 200);
    }

    /**
     * Removes a "Belonging to" relationship between user and group
     * @param Request $request
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function removeUserAction(Request $request, Application $app)
    {

        // Basic data validation
        
            if (    null == $request->get('groupName') 
                 || null == $request->get('id')
            ) {
                return $app->json(array('reason'=>'null value'), 400);
            }
            
            //comment this validation (and add cast to int in model) if
            //debugging with Postman 1.02, due to bug 831 (need to send key->value)
            /*if (!is_int($request->get('id'))) {
                return $app->json(array($request->get('id')), 400);
            }*/

        try {
            $model = $app['users.groups.model'];

            $isBelonging = $model->isUserFromGroup( $request->get('groupName'),
                                                    $request->get('id'));
            if ($isBelonging){
                $model->removeUserFromGroup($request->get('groupName'),
                                            $request->get('id'));
            }

            return  $app->json(array("wasBelonging"=>$isBelonging), 200);
        } catch (\Exception $e) {
            if ($app['env'] == 'dev') {
                throw $e;
            }

            return $app->json(array(), 500);
        }

        return $app->json(array(), 200);
    }

}