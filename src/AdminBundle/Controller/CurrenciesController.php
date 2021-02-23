<?php

namespace AdminBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use AppBundle\Entity\Currency;
use AdminBundle\Form\MyCurrencyType;

/**
 * @Route("/admin/currencies")
 */
class CurrenciesController extends Controller
{
    /**
     * @Route("/", name="admin_general_currencies")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();
        $currencies = $em->getRepository('AppBundle:Currency')->findAll();
        $currencies = $this->get('app.currency')->parse($currencies);
        
        return array(
            'currencies' => $currencies
        );
    }
    
    /**
     * @Route("/add", name="admin_general_currencies_add")
     */
    public function addAction(Request $request)
    {
        $currency = new Currency();
        $form = $this->createForm(MyCurrencyType::class, $currency);
        
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            try {
                $em = $this->getDoctrine()->getManager();
                $em->persist($currency);
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('ogolne.waluty.dodana', [], 'admin'));
                return new RedirectResponse($this->generateUrl('admin_general_currencies'));
            } catch(\Exception $e)
            {
                $this->addFlash('error', $this->get('translator')->trans('ogolne.waluty.nie_dodana', [], 'admin'));
            }
        }
        
        return $this->render('AdminBundle:Currencies:add_edit.html.twig', array(
            'form' => $form->createView()
        ));
    }
    
    /**
     * @Route("/delete/{id}", name="admin_general_currencies_delete", requirements={
     *  "id": "\d+"
     * })
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        
        $currency = $em->getRepository('AppBundle:Currency')->find($id);
        if(!$currency)
            throw $this->createNotFoundException ();
        
        $em->remove($currency);
        $em->flush();
        $this->addFlash('success', $this->get('translator')->trans('ogolne.waluty.usunieta', [], 'admin'));
        return new RedirectResponse($this->generateUrl('admin_general_currencies'));
    }
    
    /**
     * @Route("/edit/{id}", name="admin_general_currencies_edit", requirements={
     *  "id": "\d+"
     * })
     */
    public function editAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        
        $currency = $em->getRepository('AppBundle:Currency')->find($id);
        if(!$currency)
            throw $this->createNotFoundException ();
        
        $form = $this->createForm(MyCurrencyType::class, $currency);
        
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            try {
                $em = $this->getDoctrine()->getManager();
                $em->persist($currency);
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('ogolne.waluty.zmieniona', [], 'admin'));
                return new RedirectResponse($this->generateUrl('admin_general_currencies'));
            } catch(\Exception $e)
            {
                $this->addFlash('error', $this->get('translator')->trans('ogolne.waluty.nie_zmieniona', [], 'admin'));
            }
        }
        
        return $this->render('AdminBundle:Currencies:add_edit.html.twig', array(
            'form' => $form->createView(),
            'currency' => $currency
        ));
    }
}
