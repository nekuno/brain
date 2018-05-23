<?php

namespace Controller\Admin\EnterpriseUser;

use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use FOS\RestBundle\Controller\Annotations\Route;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Model\EnterpriseUser\EnterpriseUserManager;
use Symfony\Component\HttpFoundation\Request;
use Swagger\Annotations as SWG;

/**
 * @Route("/admin")
 */
class EnterpriseUserController extends FOSRestController implements ClassResourceInterface
{
    /**
     * Get enterprise user
     *
     * @Get("/enterpriseUsers/{id}", requirements={"id"="\d+"})
     * @param integer $id
     * @param EnterpriseUserManager $enterpriseUserManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns enterprise user.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function getAction($id, EnterpriseUserManager $enterpriseUserManager)
    {
        $enterpriseUser = $enterpriseUserManager->getById($id);

        return $this->view($enterpriseUser, 200);
    }

    /**
     * Create enterprise user
     *
     * @Post("/enterpriseUsers")
     * @param Request $request
     * @param EnterpriseUserManager $enterpriseUserManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=201,
     *     description="Returns created enterprise user.",
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          required={"admin_id", "username", "email"},
     *          @SWG\Property(property="admin_id", type="integer"),
     *          @SWG\Property(property="username", type="string"),
     *          @SWG\Property(property="email", type="string"),
     *      )
     * )
     * @SWG\Tag(name="admin")
     */
    public function postAction(Request $request, EnterpriseUserManager $enterpriseUserManager)
    {
        $data = $request->request->all();

        $enterpriseUser = $enterpriseUserManager->create($data);

        return $this->view($enterpriseUser, 201);
    }

    /**
     * Edit enterprise user
     *
     * @Put("/enterpriseUsers/{id}", requirements={"id"="\d+"})
     * @param integer $id
     * @param Request $request
     * @param EnterpriseUserManager $enterpriseUserManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns edited enterprise user.",
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          required={"admin_id", "username", "email"},
     *          @SWG\Property(property="admin_id", type="integer"),
     *          @SWG\Property(property="username", type="string"),
     *          @SWG\Property(property="email", type="string"),
     *      )
     * )
     * @SWG\Tag(name="admin")
     */
    public function putAction($id, Request $request, EnterpriseUserManager $enterpriseUserManager)
    {
        $data = $request->request->all();
        $data['id'] = $id;
        $enterpriseUser = $enterpriseUserManager->update($data);

        return $this->view($enterpriseUser, 200);
    }

    /**
     * Delete enterprise user
     *
     * @Delete("/enterpriseUsers/{id}", requirements={"id"="\d+"})
     * @param integer $id
     * @param EnterpriseUserManager $enterpriseUserManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Returns deleted enterprise user.",
     * )
     * @SWG\Tag(name="admin")
     */
    public function deleteAction($id, EnterpriseUserManager $enterpriseUserManager)
    {
        $enterpriseUser = $enterpriseUserManager->getById($id);
        $enterpriseUserManager->remove($id);

        return $this->view($enterpriseUser, 200);
    }

    /**
     * Validate enterprise user
     *
     * @Post("/enterpriseUsers/{id}", requirements={"id"="\d+"})
     * @param Request $request
     * @param EnterpriseUserManager $enterpriseUserManager
     * @return \FOS\RestBundle\View\View
     * @SWG\Response(
     *     response=200,
     *     description="Successful validation.",
     * )
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      type="json",
     *      schema=@SWG\Schema(
     *          required={"admin_id", "username", "email"},
     *          @SWG\Property(property="admin_id", type="integer"),
     *          @SWG\Property(property="username", type="string"),
     *          @SWG\Property(property="email", type="string"),
     *      )
     * )
     * @SWG\Tag(name="admin")
     */
    public function validateAction(Request $request, EnterpriseUserManager $enterpriseUserManager)
    {
        $data = $request->request->all();

        $enterpriseUserManager->validate($data);

        return $this->view([], 200);
    }
}