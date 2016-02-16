<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Controller\Admin\EnterpriseUser;

use Model\User\GroupModel;
use Model\EnterpriseUser\EnterpriseUserModel;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class GroupController
 * @package Controller
 */
class GroupController
{
    /**
     * @var GroupModel
     */
    protected $gm;

    /**
     * @var EnterpriseUserModel
     */
    protected $eum;

    public function __construct(GroupModel $gm, EnterpriseUserModel $eum)
    {
        $this->gm = $gm;
        $this->eum = $eum;
    }

    public function getAllAction(Application $app, $enterpriseUserId)
    {

        $groups = $this->gm->getAllByEnterpriseUserId($enterpriseUserId);

        return $app->json($groups);

    }

    public function getAction(Application $app, $id, $enterpriseUserId)
    {

        $group = $this->gm->getByIdAndEnterpriseUserId($id, $enterpriseUserId);

        return $app->json($group);
    }

    public function postAction(Request $request, Application $app, $enterpriseUserId)
    {
        $data = $request->request->all();

        if(!$this->eum->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        $group = $this->gm->create($data);
        $this->gm->setCreatedByEnterpriseUser($group['id'], $enterpriseUserId);

        return $app->json($group, 201);
    }

    public function putAction(Request $request, Application $app, $id, $enterpriseUserId)
    {
        $data = $request->request->all();

        if(!$this->eum->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        $group = $this->gm->update($id, $data);

        return $app->json($group);
    }

    public function deleteAction(Application $app, $id, $enterpriseUserId)
    {
        if(!$this->eum->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        $group = $this->gm->remove($id);

        return $app->json($group);
    }

    public function validateAction(Request $request, Application $app, $enterpriseUserId)
    {
        $data = $request->request->all();

        if(!$this->eum->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        $this->gm->validate($data);

        return $app->json();
    }

}