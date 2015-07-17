<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Controller\EnterpriseUser;

use Model\User\InvitationModel;
use Model\EnterpriseUser\EnterpriseUserModel;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class InvitationController
 * @package Controller
 */
class InvitationController
{
    /**
     * @var InvitationModel
     */
    protected $im;

    /**
     * @var EnterpriseUserModel
     */
    protected $eum;

    public function __construct(InvitationModel $im, EnterpriseUserModel $eum)
    {
        $this->im = $im;
        $this->eum = $eum;
    }

    public function getAction(Application $app, $id, $enterpriseUserId)
    {
        if(!$this->eum->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        $invitation = $this->im->getById($id);

        return $app->json($invitation);
    }

    public function postAction(Request $request, Application $app, $enterpriseUserId)
    {
        $data = $request->request->all();

        if(!$this->eum->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        $invitation = $this->im->create($data, $app['tokenGenerator.service']);

        return $app->json($invitation, 201);
    }

    public function deleteAction(Application $app, $id, $enterpriseUserId)
    {
        if(!$this->eum->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        $invitation = $this->im->getById($id);
        $this->im->remove($id);

        return $app->json($invitation);
    }

    public function validateAction(Request $request, Application $app, $enterpriseUserId)
    {
        $data = $request->request->all();

        if(!$this->eum->exists($enterpriseUserId)) {
            throw new NotFoundHttpException(sprintf('There is not enterprise user with id "%s"', $enterpriseUserId));
        }

        $this->im->validate($data);

        return $app->json();
    }

}