<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * @Route("/invoices")
 */
class InvoiceController extends Controller
{
    /**
     * @Route("/", name="user_invoice")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        if(!($user = $this->getUser()))
            throw $this->createAccessDeniedException();

        $em = $this->getDoctrine()->getManager();

        $query = $em->getRepository('AppBundle:Invoice')->createQueryBuilder('i')
            ->where('i.user = :user')
            ->setParameter(':user', $user);

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            25
        );

        return array(
            'pagination' => $pagination
        );
    }

    /**
     * @Route("/generate/{id}", name="user_invoice_generate", requirements={
     *  "id": "\d+"
     * })
     */
    public function generateAction($id, Request $request)
    {
        if(!($user = $this->getUser()))
            throw $this->createAccessDeniedException();

        $em = $this->getDoctrine()->getManager();

        $invoice = $em->getRepository('AppBundle:Invoice')->find($id);
        if(!$invoice)
            throw $this->createNotFoundException();

        if(!$this->isGranted('ROLE_ADMIN'))
        {
            if($invoice->getUser()->getId() != $user->getId())
                throw $this->createNotFoundException();
        }

        $total = 0;
        foreach($invoice->getProducts() as $product)
        {
            $total += $product['netto'];
        }
        $vat = round($invoice->getTax()*$total/100, 2);
        $total_vat = $total+$vat;

        $inc = explode('.', (string)$total_vat);
        $zlotych = $inc[0];
        $groszy = isset($inc[1]) ? $inc[1] : false;

        $locale = $request->getSession()->get('_locale');
        if(strtolower($locale) == 'pl')
        {
            $numberFormatter = new \NumberFormatter("pl_PL", \NumberFormatter::SPELLOUT);
        }
        else
        {
            $numberFormatter = new \NumberFormatter("en-US", \NumberFormatter::SPELLOUT);
        }

        $url = $this->getParameter('tmp_directory') . DIRECTORY_SEPARATOR . 'invoice_'.$invoice->getNumber().'.pdf';

        $this->get('knp_snappy.pdf')->generateFromHtml(
            $this->renderView('AppBundle:Invoice:render.html.twig', array(
                'invoice'  => $invoice,
                'total' => $total,
                'vat' => $vat,
                'total_vat' => $total+$vat,
                'in_word_zlotych' => $numberFormatter->format($zlotych),
                'in_word_groszy' => $groszy ? $numberFormatter->format($groszy) : false,
                'in_word_all' => $numberFormatter->format((string)$total_vat)
            )),
            $url, [], true
        );

        $respone = new BinaryFileResponse($url);
        $respone->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

        return $respone;
    }
}
