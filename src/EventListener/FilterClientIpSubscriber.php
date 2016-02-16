<?php
/**
 * @author Manolo Salsas (manolez@gmail.com)
 */
namespace EventListener;

use Firebase\JWT\JWT;
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
    protected $sharedActions = array();

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

        if ($this->isAdminAction($request) && !$this->isValidIp($request)) {
            throw new AccessDeniedHttpException('Access forbidden');
        }

        if ($this->isSharedAction($request) && $this->isValidIp($request)) {
            return;
        }

        if ($request->headers->has('authorization')) {
            list($jwt) = sscanf($request->headers->get('authorization'), 'Bearer %s');
            try {
                JWT::decode($jwt, $this->secret, array('HS256'));
            } catch (\Exception $e) {
                throw new UnauthorizedHttpException('', 'JWT token not valid');
            }
        }

    }

    protected function isAdminAction(Request $request)
    {
        return in_array($request->attributes->get('_controller'), $this->adminActions);
    }

    protected function isSharedAction(Request $request)
    {
        return in_array($request->attributes->get('_controller'), $this->sharedActions);
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
            'users.invitations.controller:validateTokenAction',
            'users.profile.controller:getMetadataAction',
            'users.controller:postAction',
            'users.controller:validateAction',
            'users.controller:availableAction',
            'users.profile.controller:validateAction',
        );

        $this->adminActions = array(
            'enterpriseUsers.controller:getAction',
            'enterpriseUsers.controller:postAction',
            'enterpriseUsers.controller:putAction',
            'enterpriseUsers.controller:deleteAction',
            'enterpriseUsers.controller:validateAction',
            'enterpriseUsers.groups.controller:getAllAction',
            'enterpriseUsers.groups.controller:getAction',
            'enterpriseUsers.groups.controller:postAction',
            'enterpriseUsers.groups.controller:putAction',
            'enterpriseUsers.groups.controller:deleteAction',
            'enterpriseUsers.groups.controller:validateAction',
            'enterpriseUsers.communities.controller:getByGroupAction',
            'enterpriseUsers.invitations.controller:postAction',
            'enterpriseUsers.invitations.controller:deleteAction',
            'enterpriseUsers.invitations.controller:getAction',
            'enterpriseUsers.invitations.controller:putAction',
            'enterpriseUsers.invitations.controller:validateAction',
            'users.groups.controller:getAllAction',
            'users.groups.controller:postAction',
            'users.groups.controller:putAction',
            'users.groups.controller:deleteAction',
            'users.groups.controller:validateAction',
            'users.invitations.controller:indexAction',
        );

        $this->sharedActions = array(
            'users.invitations.controller:getAction',
            'users.invitations.controller:postAction',
            'users.invitations.controller:putAction',
            'users.invitations.controller:deleteAction',
            'users.invitations.controller:validateAction',
            'users.groups.controller:getAction',
        );
    }
}
