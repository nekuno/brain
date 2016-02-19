<?php
/**
 * @author Manolo Salsas (manolez@gmail.com)
 */
namespace EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class FilterClientIpSubscriber
 * @package EventListener
 */
class FilterClientIpSubscriber implements EventSubscriberInterface
{
    protected $validIps = array();
    protected $secret;
    protected $adminActions = array();
    protected $publicActions = array();

    public function __construct(array $validIps, $secret)
    {
        $this->validIps = $validIps;
        $this->secret = $secret;
        $this->setActionTypes();
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => array('onControllerFound'),
        );
    }

    public function onControllerFound(FilterControllerEvent $event)
    {
        $controller = $event->getController();
        $request = $event->getRequest();

        if (!is_array($controller)) {
            return;
        }

        if ($this->isPublicAction($request)) {
            return;
        }

        if ($this->isAdminAction($request) && $this->isValidIp($request)) {
            return;
        }

        if ($this->isAdminAction($request) && !$this->isValidIp($request)) {
            throw new AccessDeniedHttpException('Access forbidden');
        }

        if ($request->headers->has('authorization')) {
            list($jwt) = sscanf($request->headers->get('authorization'), 'Bearer %s');
            try {
                $decodedJwt = \JWT::decode($jwt, $this->secret, array('HS256'));
                $request->request->set('userId', (int)$decodedJwt->user->qnoow_id);
            } catch (\Exception $e) {
                throw new UnauthorizedHttpException('', 'JWT token not valid');
            }
        }
        else {
            throw new UnauthorizedHttpException('', 'JWT token not sent');
        }

    }

    protected function isAdminAction(Request $request)
    {
        return in_array($request->attributes->get('_controller'), $this->adminActions);
    }

    protected function isPublicAction(Request $request)
    {
        return in_array($request->attributes->get('_controller'), $this->publicActions);
    }

    protected function isValidIp(Request $request)
    {
        return in_array($request->getClientIp(), $this->validIps);
    }

    protected function setActionTypes()
    {
        $this->publicActions = array(
            'lookUp.controller:setFromWebHookAction',
            'auth.controller:loginAction',
            'auth.controller:preflightAction',
            'users.controller:findAction',
            'users.controller:postAction',
            'users.tokens.controller:getAllAction',
            'users.invitations.controller:validateTokenAction',
            'users.profile.controller:getMetadataAction',
            'users.controller:postAction',
            'users.controller:validateAction',
            'users.controller:availableAction',
            'users.profile.controller:validateAction',
            'client.controller:versionAction',
        );

        $this->adminActions = array(
            'admin.enterpriseUsers.controller:getAction',
            'admin.enterpriseUsers.controller:postAction',
            'admin.enterpriseUsers.controller:putAction',
            'admin.enterpriseUsers.controller:deleteAction',
            'admin.enterpriseUsers.controller:validateAction',
            'admin.enterpriseUsers.groups.controller:getAllAction',
            'admin.enterpriseUsers.groups.controller:getAction',
            'admin.enterpriseUsers.groups.controller:postAction',
            'admin.enterpriseUsers.groups.controller:putAction',
            'admin.enterpriseUsers.groups.controller:deleteAction',
            'admin.enterpriseUsers.groups.controller:validateAction',
            'admin.enterpriseUsers.communities.controller:getByGroupAction',
            'admin.enterpriseUsers.invitations.controller:postAction',
            'admin.enterpriseUsers.invitations.controller:deleteAction',
            'admin.enterpriseUsers.invitations.controller:getAction',
            'admin.enterpriseUsers.invitations.controller:putAction',
            'admin.enterpriseUsers.invitations.controller:validateAction',
            'admin.groups.controller:getAction',
            'admin.groups.controller:getAllAction',
            'admin.groups.controller:postAction',
            'admin.groups.controller:putAction',
            'admin.groups.controller:deleteAction',
            'admin.groups.controller:validateAction',
            'admin.invitations.controller:indexAction',
            'admin.invitations.controller:getAction',
            'admin.invitations.controller:postAction',
            'admin.invitations.controller:putAction',
            'admin.invitations.controller:deleteAction',
            'admin.invitations.controller:validateAction'
        );
    }
}
