<?php

namespace Pilotabai\CompetitionDbBundle\EventListener;

use Pilotabai\CompetitionDbBundle\Api\ApiProbException;
use Pilotabai\CompetitionDbBundle\Api\ApiProblem;
use Pilotabai\CompetitionDbBundle\Api\ResponseFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    private $debug;
    private $responseFactory;

    /**
     * ApiExceptionSubscriber constructor.
     */
    public function __construct($debug, ResponseFactory $responseFactory)
    {
        $this->debug = $debug;
        $this->responseFactory = $responseFactory;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (strpos($event->getRequest()->getPathInfo(), '/api') !== 0) {
            return;
        }
        $e = $event->getException();
        if ($e instanceof ApiProbException) {
            $apiProblem = $e->getApiProblem();
        } else {
            $statusCode = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
            if ($statusCode == 500 && $this->debug) {
                return;
            }
            $apiProblem = new ApiProblem(
                $statusCode
            );
            /*
             * If it's an HttpException message (e.g. for 404, 403),
             * we'll say as a rule that the exception message is safe
             * for the client. Otherwise, it could be some sensitive
             * low-level exception, which should *not* be exposed
             */
            if ($e instanceof HttpExceptionInterface) {
                $apiProblem->set('detail', $e->getMessage());
            }
        }

//        $response = new JsonResponse(
//            $apiProblem->toArray(),
//            $apiProblem->getStatusCode()
//        );
//        $response->headers->set('Content-Type', 'application/problem+json');

//        $data = $apiProblem->toArray();
//        // making type a URL, to a temporarily fake page
//        if ($data['type'] != 'about:blank') {
//            $data['type'] = 'http://localhost:8000/docs/errors#'.$data['type'];
//        }
//        $response = new JsonResponse(
//            $data,
//            $apiProblem->getStatusCode()
//        );
//        $response->headers->set('Content-Type', 'application/problem+json');

        $response = $this->responseFactory->createResponse($apiProblem);

        $event->setResponse($response);
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::EXCEPTION => 'onKernelException' // name of our method in this class that should be called whenever an exception is thrown
        );
    }
}