<?php

namespace AdminBundle\Controller;

use AdminBundle\Form\InvoiceType;
use AppBundle\Entity\Invoice;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin/invoices")
 */
class InvoiceController extends Controller
{
    /**
     * @Route("/", name="admin_invoices_index")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $query = $em->getRepository('AppBundle:Invoice')->createQueryBuilder('i')
            ->join('i.user', 'u')->addSelect('u')
            ->join('i.currency', 'c')->addSelect('c');

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
     * @Route("/delete/{id}", name="admin_invoices_delete", requirements={
     *  "id": "\d+"
     * })
     */
    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $invoice = $em->getRepository('AppBundle:Invoice')->find($id);
        if(!$invoice)
            throw $this->createNotFoundException();

        $em->remove($invoice);
        $em->flush();
        $this->addFlash('success', 'Faktura została poprawnie usunięta.');
        return new RedirectResponse($this->generateUrl('admin_invoices_index'));
    }

    /**
     * @Route("/add/{user_id}", name="admin_invoices_add", requirements={
     *  "user_id": "\d+"
     * })
     * @Template()
     */
    public function addAction(Request $request, $user_id)
    {
        $em = $this->getDoctrine()->getManager();
        $userManger = $this->get('fos_user.user_manager');

        $user = $userManger->findUserBy(array('id' => $user_id));
        if(!$user)
            throw $this->createNotFoundException();

        $buyer = '';
        if($user->getInvoiceFullname())
        {
            $buyer = $user->getInvoiceFullname().PHP_EOL.
                     $user->getInvoiceAddress().', '.$user->getInvoicePostalCode().PHP_EOL.
                     $user->getInvoiceCity();
            if($user->getInvoiceNip())
            {
                $buyer .= "/n".$user->getInvoiceNip();
            }
        }

        $invoice = new Invoice();
        $invoice->setUser($user);
        $invoice->setExposeDate(new \DateTime('now'));
        $invoice->setSellDate(new \DateTime('now'));
        $invoice->setBuyer($buyer);
        $invoice->setNumber(strtoupper(uniqid()));

        $form = $this->createForm(InvoiceType::class, $invoice);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            try {
                $em->persist($invoice);
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('faktury.wystawiono', [], 'admin'));
                return new RedirectResponse($this->generateUrl('admin_invoices_index'));
            }
            catch(\Exception $e)
            {
                $this->addFlash('error', $this->get('translator')->trans('faktury.nie_wystawiono', [], 'admin'));
            }
        }

        return array(
            'user' => $user,
            'form' => $form->createView()
        );
    }
}
