<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CurrenciesController extends Controller
{

    /**
     * @Route("/currency/{currency}", name="change_currency")
     */
    public function changeAction($currency)
    {
        $em = $this->getDoctrine()->getManager();

        $currency = $em->getRepository('AppBundle:Currency')->findBy(array(
            'code' => $currency
        ));
        if(!$currency)
            throw $this->createNotFoundException();

        $this->get('session')->set('_currency', strtoupper($currency[0]->getCode()));

        $lastRoute = $this->get('session')->get('last_route');
        if (!empty($lastRoute) && !empty($lastRoute['name'])) {
            return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
        } else {
            return new RedirectResponse(($this->generateUrl('homepage')));
        }
    }
    
    /**
     * @Template()
     */
    public function getAction()
    {
        $em = $this->getDoctrine()->getManager();

        $currencies = $em->getRepository('AppBundle:Currency')->findBy(array(
            'enabled' => true
        ));

        return array(
            'currencies' => $currencies
        );
    }
}
