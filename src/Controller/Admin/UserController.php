<?php

namespace Controller\Admin;

use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\User\UserPaginatedManager;
use Nelmio\ApiDocBundle\Annotation\Model;
use Paginator\Paginator;
use Service\AuthService;
use Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Swagger\Annotations as SWG;

/**
 * @Route("/admin")
 */
class UserController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get jwt
     *
     * @Get("/users/jwt/{id}", requirements={"id"="\d+"})
     * @param integer $id
     * @param AuthService $authService
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns jwt.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function jwtAction($id, AuthService $authService)
    {
        $jwt = $authService->getToken($id);

        return $this->view(['jwt' => $jwt], 200);
    }

    /**
     * Get paginated users
     *
     * @Get("/users")
     * @param Request $request
     * @param Paginator $paginator
     * @param UserPaginatedManager $userPaginatedManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="order",
     *      in="query",
     *      type="string",
     * )
     * @SWG\Parameter(
     *      name="orderDir",
     *      in="query",
     *      type="string",
     * )
     * @SWG\Parameter(
     *      name="offset",
     *      in="query",
     *      type="integer",
     *      default=0,
     * )
     * @SWG\Parameter(
     *      name="limit",
     *      in="query",
     *      type="integer",
     *      default=20,
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns users.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function getUsersAction(Request $request, Paginator $paginator, UserPaginatedManager $userPaginatedManager)
    {
        $order = $request->get('order', null);
        $orderDir = $request->get('orderDir', null);
        $filters = array(
            'order' => $order,
            'orderDir' => $orderDir,
        );

        $result = $paginator->paginate($filters, $userPaginatedManager, $request);

        return $this->view($result, 200);
    }

    /**
     * Get user
     *
     * @Get("/users/{userId}", requirements={"userId"="\d+"})
     * @param integer $userId
     * @param UserService $userService
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns user.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function getUserAction($userId, UserService $userService)
    {
        $user = $userService->getOneUser($userId);

        return $this->view($user, 200);
    }

    /**
     * Edit user
     *
     * @Put("/users/{userId}", requirements={"userId"="\d+"})
     * @param integer $userId
     * @param Request $request
     * @param UserService $userService
     * @return \FOS\RestBundle\View\View
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          ref=@Model(type=\Model\User\User::class)
     *      )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Returns edited user.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function updateUserAction($userId, Request $request, UserService $userService)
    {
        $data = $request->request->all();
        $data['userId'] = $userId;
        $user = $userService->updateUser($data);

        return $this->view($user, 200);
    }

    /**
     * Delete user
     *
     * @Delete("/users/{userId}", requirements={"userId"="\d+"})
     * @param integer $userId
     * @param UserService $userService
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns deleted user.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function deleteUserAction($userId, UserService $userService)
    {
        $user = $userService->deleteUser((integer)$userId);

        return $this->view($user, 200);
    }
}