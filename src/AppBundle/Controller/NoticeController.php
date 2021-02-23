<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Notice;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Intl\Intl;

/**
 * @Route("user/notice")
 */
class NoticeController extends Controller
{

    
    /**
     * @Route("/{id}/read", name="user_notice_read", requirements={
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
            ->andWhere('n.recipient = :recipient')
            ->setParameter(':id', $id )
            ->setParameter(':recipient', $this->getUser() )
            ->getQuery()->getResult();
        

        $return = array(
            'notice' => clone $notices[0]
        );
        $notices[0]->setIsReaded();
        
        $em->persist( $notices[0] );
        $em->flush();
        return $return;
    }

    /**
     * @Route("s", name="user_notices")
     * @Template()
     */
    public function listAction(Request $request)
    {
        if( !$this->getUser() )
            throw $this->createAccessDeniedException();

        $em = $this->getDoctrine()->getManager();

        $notices = $em->getRepository('AppBundle:Notice')->createQueryBuilder('n')
            ->leftJoin('n.author', 'a')->addSelect('a')
            ->leftJoin('n.recipient', 'r')->addSelect('r')
            ->where('n.recipient = :recipient')
            ->setParameter(':recipient', $this->getUser() );
            
        
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
    /**
     * @Route("s/have-not-readed", name="user_notices_have_not_readed")
     * @Template()
     */
    public function haveNewAction(Request $request)
    {
        if( !$this->getUser() )
            throw $this->createAccessDeniedException();

        $em = $this->getDoctrine()->getManager();

        $is_not_readed_notices = $em->getRepository('AppBundle:Notice')->createQueryBuilder('n') 
            ->select('COUNT( n.id ) as notreadnotices')
            ->leftJoin('n.author', 'a')
            ->leftJoin('n.recipient', 'r')
            ->where('n.recipient = :recipient')
            ->andWhere('n.is_readed = :is_readed')
            ->setParameter(':recipient', $this->getUser() )
            ->setParameter(':is_readed', false )
            ->getQuery()->getSingleResult();
        
        $lastRoute = $this->get('session')->get('last_route');
        //$session->set('last_route', $thisRoute);
        $this->get('session')->set('this_route', $lastRoute);        
        return new JsonResponse( $is_not_readed_notices );
    }

}
