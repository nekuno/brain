<?php

namespace EventListener;

use Model\Exception\ValidationException;
use Model\Neo4j\Neo4jException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ControllerSubscriber implements EventSubscriberInterface
{
    protected $env;

    public function __construct($env)
    {
        $this->env = $env;
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::RESPONSE => array('onKernelResponse'),
            KernelEvents::CONTROLLER => array('onKernelController'),
            KernelEvents::EXCEPTION => array('onKernelException'),
        );
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        // Parse request content and populate parameters
        if (($request->getContentType() === 'application/json' || $request->getContentType() === 'json') && $request->getContent()) {
            $encoding = mb_detect_encoding($request->getContent(), 'auto');
            $content = $encoding === 'UTF-8' ? $request->getContent() : utf8_encode($request->getContent());
            $data = json_decode($content, true);
            if (json_last_error()) {
                throw new \Exception('Error parsing JSON data.');
            }
            $request->request->replace(is_array($data) ? $data : array());
        }
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $response->headers->set('Access-Control-Allow-Origin', '*');
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $e = $event->getException();
        $data = array('error' => $e->getMessage());

        $headers = $e instanceof HttpExceptionInterface ? $e->getHeaders() : array();
        $statusCode = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

        if ($e instanceof ValidationException) {
            $data['validationErrors'] = $e->getErrors();
        }

        if ($e instanceof Neo4jException) {
            $data['error'] = isset($e->getData()['message']) ? $e->getData()['message'] : $e->getData() ? $e->getData() : $e->getMessage();
            $data['query'] = $e->getQuery();
            $data['headers'] = $e->getHeaders();
            $data['data'] = $e->getData();
        }

        if ($this->env === 'debug') {
            $data['debug'] = array(
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => explode("\n", $e->getTraceAsString())
            );
        }

        $response = new JsonResponse($data, $statusCode, $headers);

        $event->setResponse($response);
    }
}
