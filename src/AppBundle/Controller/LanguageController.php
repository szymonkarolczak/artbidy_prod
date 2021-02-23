<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LanguageController extends Controller
{
    /**
     * @Route("/lang/{lang}", name="change_lang")
     */
    public function changeAction($lang)
    {
        $em = $this->getDoctrine()->getManager();

        $lang = $em->getRepository('AppBundle:Language')->findBy(array(
            'shortcut' => $lang
        ));
        if(!$lang)
            throw $this->createNotFoundException();

        $this->get('session')->set('_locale', $lang[0]->getShortcut());

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

        $langs = $em->getRepository('AppBundle:Language')->findBy(array(
            'enabled' => true
        ));

        return array(
            'langs' => $langs
        );
    }
}
