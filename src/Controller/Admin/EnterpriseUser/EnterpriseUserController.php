<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Controller\Admin\EnterpriseUser;

use Model\EnterpriseUser\EnterpriseUserModel;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class EnterpriseUserController
 * @package Controller
 */
class EnterpriseUserController
{
    /**
     * @var EnterpriseUserModel
     */
    protected $eum;

    public function __construct(EnterpriseUserModel $eum)
    {
        $this->eum = $eum;
    }

    public function getAction(Application $app, $id)
    {

        $enterpriseUser = $this->eum->getById($id);

        return $app->json($enterpriseUser);
    }

    public function postAction(Request $request, Application $app)
    {
        $data = $request->request->all();

        $enterpriseUser = $this->eum->create($data);

        return $app->json($enterpriseUser, 201);
    }

    public function putAction(Request $request, Application $app, $id)
    {
        $data = $request->request->all();

        $enterpriseUser = $this->eum->update($id, $data);

        return $app->json($enterpriseUser, 200);
    }

    public function deleteAction(Application $app, $id)
    {
        $enterpriseUser = $this->eum->getById($id);
        $this->eum->remove($id);

        return $app->json($enterpriseUser);
    }

    public function validateAction(Request $request, Application $app)
    {
        $data = $request->request->all();

        $this->eum->validate($data);

        return $app->json();
    }
}