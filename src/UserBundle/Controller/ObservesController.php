<?php

namespace UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/user/observes")
 */
class ObservesController extends Controller
{
    /**
     * @Route("/", name="user_observes")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        if(!$this->isGranted('IS_AUTHENTICATED_REMEMBERED'))
            throw $this->createAccessDeniedException();

        $em = $this->getDoctrine()->getManager();
        
        $query = $em->getRepository('AppBundle:FineartsAuctionObserve')->createQueryBuilder('fao')
            ->join('fao.auction', 'a')->addSelect('a')
            ->join('a.langs', 'al')->addSelect('al.title, al.description')
            ->join('al.lang', 'l')
            ->where('fao.user = :user')
            ->andWhere('l.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale())
            ->setParameter(':user', $this->getUser());
        
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
     * @Route("/delete/{id}", name="user_observes_delete", requirements={
     *  "id": "\d+"
     * })
     */
    public function deleteAction($id, Request $request)
    {
        if(!$this->isGranted('IS_AUTHENTICATED_REMEMBERED'))
            throw $this->createAccessDeniedException();

        $em = $this->getDoctrine()->getManager();
        
        if($request->get('type') == 'object')
            $observe = $em->getRepository('AppBundle:AuctionWorkObserve')->find($id);
        else
            $observe = $em->getRepository('AppBundle:FineartsAuctionObserve')->find($id);

        if(!$observe || $observe->getUser()->getId() != $this->getUser()->getId())
            throw $this->createNotFoundException ();
        
        $em->remove($observe);
        $em->flush();
        $this->addFlash('error', $this->get('translator')->trans('obserwuj.usunieto'));

        $lastRoute = $this->get('session')->get('last_route');
        if (!empty($lastRoute) && !empty($lastRoute['name'])) {
            return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
        } else {
            return new RedirectResponse(($this->generateUrl('homepage')));
        }
    }

    /**
     * @Route("/works", name="user_observes_objects")
     * @Template()
     */
    public function objectsAction(Request $request)
    {
        if(!$this->isGranted('IS_AUTHENTICATED_REMEMBERED'))
            throw $this->createAccessDeniedException();

        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $query = $em->getRepository('AppBundle:AuctionWorkObserve')->createQueryBuilder('awo')
            ->join('awo.auctionWork', 'aw')->addSelect('aw')
            ->join('aw.auction', 'a')->addSelect('a')
            ->join('aw.work', 'w')->addSelect('w')
            ->join('a.langs', 'al')->addSelect('al.title')
            ->join('al.lang', 'alla')
            ->where('awo.user = :user')
            ->andWhere('alla.shortcut = :shortcut')
            ->setParameter(':user', $user)
            ->setParameter(':shortcut', $request->getLocale());

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
     * @Route("/houseauction", name="user_observes_houseauction")
     * @Template()
     */
    public function houseAuctionAction()
    {
        if(!$this->isGranted('IS_AUTHENTICATED_REMEMBERED'))
            throw $this->createAccessDeniedException();
        
        $now = new \DateTime('now');
        $user = $this->getUser();
        if(!$user->getAnnoucement() || $user->getAnnoucement() < $now)
        {
            $this->addFlash('error', $this->get('translator')->trans('obserwuj.kup_usluge'));
            return new \Symfony\Component\HttpFoundation\RedirectResponse($this->generateUrl('footer_information', array(
                'page' => 'annoucements'
            )));
        }
        
        return array();
    }
    
    /**
     * @Route("/users", name="user_observes_users")
     * @Template()
     */
    public function usersAction()
    {
        if(!$this->isGranted('IS_AUTHENTICATED_REMEMBERED'))
            throw $this->createAccessDeniedException();
        
        $now = new \DateTime('now');
        $user = $this->getUser();
        if(!$user->getAnnoucement() || $user->getAnnoucement() < $now)
        {
            $this->addFlash('error', $this->get('translator')->trans('obserwuj.kup_usluge'));
            return new \Symfony\Component\HttpFoundation\RedirectResponse($this->generateUrl('footer_information', array(
                'page' => 'annoucements'
            )));
        }
        
        return array();
    }

}
