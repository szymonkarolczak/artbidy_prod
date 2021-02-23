<?php

namespace AdminBundle\Controller;

use AppBundle\Entity\Notice;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Intl\Intl;

/**
 * @Route("admin/notice")
 */
class NoticeController extends Controller
{

    
    /**
     * @Route("/{id}/read", name="admin_notice_read", requirements={
     *  "id": "\d+"
     * })
     * @Template()
     */
    public function readAction($id, Request $request)
    {
        if( !$this->getUser() )
            throw $this->createAccessDeniedException();
        
        $em = $this->getDoctrine()->getManager();

        $notices = $em->getRepository('AppBundle:Notice')->createQueryBuilder('n')
            ->leftJoin('n.author', 'a')->addSelect('a')
            ->leftJoin('n.recipient', 'r')->addSelect('r')
            ->where('n.id = :id')
            ->setParameter(':id', $id )
            ->getQuery()->getResult();
        

        return array(
            'notice' => $notices[0]
        );
    }
    
    /**
     * @Route("/{id}/delete", name="admin_notice_delete", requirements={
     *  "id": "\d+"
     * })
     * @Template()
     */
    public function deleteAction($id, Request $request)
    {
        if( !$this->getUser() )
            throw $this->createAccessDeniedException();

        $em = $this->getDoctrine()->getManager();

        $notice = $em->getRepository('AppBundle:Notice')->find( $id );

        if(!$notice)
            throw $this->createNotFoundException();

        try {
            $em->remove($notice);
            $em->flush();
            $this->addFlash('success', $this->get('translator')->trans('aktualnosci.usuniety', array(), 'admin'));
        } catch(\Exception $e)
        {
            $this->addFlash('error', 'Nie udało się usunąć notice. '.$e->getMessage());
        }
        return new RedirectResponse($this->generateUrl('admin_notices'));
    }

    /**
     * @Route("s", name="admin_notices")
     * @Template()
     */
    public function listAction(Request $request)
    {
        if( !$this->getUser() )
            throw $this->createAccessDeniedException();

        $em = $this->getDoctrine()->getManager();

        $notices = $em->getRepository('AppBundle:Notice')->createQueryBuilder('n')
            ->leftJoin('n.author', 'a')->addSelect('a')
            ->leftJoin('n.recipient', 'r')->addSelect('r');
            
        
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $notices,
            $request->query->getInt('page', 1),
            25
        );
        
        return array(
            'pagination' => $pagination
        );
    }
}
