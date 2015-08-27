<?php
/**
 * @author Manolo Salsas (manolez@gmail.com)
 */
namespace EventListener;

use Controller\User\LookUpController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class FilterClientIpSubscriber
 * @package EventListener
 */
class FilterClientIpSubscriber implements EventSubscriberInterface
{
    private $validIps = array();
    private $lookupValidIps = array();

    public function __construct(array $validIps, array $lookupValidIps)
    {
        $this->validIps = $validIps;
        $this->lookupValidIps = $lookupValidIps;
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

        if (!is_array($controller)) {
            return;
        }

        if ($controller[0] instanceof LookUpController) {
            if (!in_array($event->getRequest()->getClientIp(), $this->lookupValidIps)) {
                throw new AccessDeniedHttpException('Access forbidden');
            }
        }
        else {
            if (!in_array($event->getRequest()->getClientIp(), $this->validIps)) {
                throw new AccessDeniedHttpException('Access forbidden');
            }
        }
    }
}
