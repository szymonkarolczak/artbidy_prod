<?php


namespace AppBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

class Redirect404ToHomepageListener implements ListenerInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @var GetResponseForExceptionEvent $event
     * @return null
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        // If not a HttpNotFoundException ignore
        if (!$event->getException() instanceof NotFoundHttpException) {
            return;
        }
        // Create redirect response with url for the home page
        $response = new RedirectResponse($this->router->generate('pl_en__RG__homepage'));

        // Set the response to be processed
        $event->setResponse($response);
    }

    /**
     * This interface must be implemented by firewall listeners.
     *
     * @param GetResponseEvent $event
     */
    public function handle(GetResponseEvent $event)
    {
        // TODO: Implement handle() method.
    }
}