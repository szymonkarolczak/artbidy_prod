<?php
// src/AppBundle/EventListener/LocaleListener.php
namespace AppBundle\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LocaleListener implements EventSubscriberInterface
{
    private $defaultLocale;
    private $defaultCurrency;

    public function __construct($defaultLocale = 'pl', $defaultCurrency = 'PLN')
    {
        $this->defaultLocale = $defaultLocale;
        $this->defaultCurrency = $defaultCurrency;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if($fb_locale = $request->get('fb_locale'))
        {
            $inc = explode('_', $fb_locale);
            $fbLang = $inc[0];
            if(strtolower($fbLang) == 'pl')
            {
                $locale = 'pl';
                $request->setLocale('pl');
            }
            else
            {
                $locale = 'en';
                $request->setLocale('en');
            }
        } else {
            $preferedLang = $request->getPreferredLanguage(array('pl', 'en'));
            $currentLocale = $request->getSession()->get('_locale');

            if ($locale = $request->attributes->get('_locale')) {
                $request->getSession()->set('_locale', $locale);
            } else {
                $locale = $preferedLang;
                $request->setLocale($request->getSession()->get('_locale', $preferedLang));
            }
        }

        $currentCurrency = $request->getSession()->get('_currency');
        if(!$currentCurrency)
        {
            if(strtolower($locale) != 'pl')
            {
                $request->getSession()->set('_currency', 'USD');
            }
            else
            {
                $request->getSession()->set('_currency', $this->defaultCurrency);
            }
        }

    }

    public static function getSubscribedEvents()
    {
        return array(
            // must be registered after the default Locale listener
            KernelEvents::REQUEST => array(array('onKernelRequest', 15)),
        );
    }
}