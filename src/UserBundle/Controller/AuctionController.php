<?php

namespace UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/user/auctions")
 */
class AuctionController extends Controller
{
    /**
     * @Route("/work", name="user_auctions_works")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        if(!$this->isGranted('IS_AUTHENTICATED_REMEMBERED'))
            throw $this->createAccessDeniedException();

        $em = $this->getDoctrine()->getManager();
        $qb = $em->getRepository('AppBundle:AuctionWork')->createQueryBuilder('aw');

        $query = $qb
            ->join('aw.work', 'w')->addSelect('w')
            ->leftJoin('aw.bids', 'b')->addSelect('b')
            ->leftJoin('b.author', 'at')->addSelect('at')
            ->join('aw.auction', 'a')
            ->join('a.langs', 'l')->addSelect('l.title, l.description')
            ->join('l.lang', 'lg')
            ->where('w.author = :author')
            ->andWhere('lg.shortcut = :shortcut')
            ->orderBy('w.id', 'DESC')
            ->setParameter(':shortcut', $request->getLocale())
            ->setParameter(':author', $this->getUser());

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            25
        );

        $filters = $request->get('_filter');
        if(isset($filters['artist']) && !empty($filters['artist']))
        {
            $query->andWhere($qb->expr()->like('w.artist', ':artist'));
            $query->setParameter(':artist', '%'.$filters['artist'].'%');
        }
        if(isset($filters['title']) && !empty($filters['title']))
        {
            $query->andWhere($qb->expr()->like('w.title', ':title'));
            $query->setParameter(':title', '%'.$filters['title'].'%');
        }
        if(isset($filters['technique']) && !empty($filters['technique']))
        {
            $query->andWhere($qb->expr()->like('w.technique', ':technique'));
            $query->setParameter(':technique', '%'.$filters['technique'].'%');
        }
        if(isset($filters['style']) && !empty($filters['style']))
        {
            $query->andWhere($qb->expr()->like('w.style', ':style'));
            $query->setParameter(':style', '%'.$filters['style'].'%');
        }

        return array(
            'pagination' => $pagination
        );
    }

    /**
     * @Route("/delete/{id}", name="user_auctions_works_delete", requirements={
     *  "id": "\d+"
     * })
     */
    public function deleteAction($id)
    {
        if(!($user = $this->getUser()))
            throw $this->createNotFoundException();

        $em = $this->getDoctrine()->getManager();
        $auctionWork = $em->getRepository('AppBundle:AuctionWork')->find($id);
        if(!$auctionWork || $auctionWork->getWork()->getAuthor()->getId() != $user->getId())
            throw $this->createNotFoundException();

        $lastRoute = $this->get('session')->get('last_route');

        if($auctionWork->getApproved())
        {
            $this->addFlash('error', $this->get('translator')->trans('profile.aukcje.nie_usunieto_zaakceptowane'));
            if (!empty($lastRoute) && !empty($lastRoute['name'])) {
                return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
            } else {
                return new RedirectResponse(($this->generateUrl('homepage')));
            }
        }

        try {
            $em->remove($auctionWork);
            $em->flush();
            $this->addFlash('success', $this->get('translator')->trans('profile.aukcje.usunieto'));
        } catch(\Exception $e)
        {
            $this->addFlash('error', $this->get('translator')->trans('profile.aukcje.nie_usunieto'));
        }


        return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
    }

    /**
     * @Route("/bids", name="user_auctions_bids")
     * @Template()
     */
    public function bidsAction(Request $request)
    {
        if(!($user = $this->getUser()))
            throw $this->createNotFoundException();

        $em = $this->getDoctrine()->getManager();

        $bids = $em->getRepository('AppBundle:AuctionBid')->createQueryBuilder('ab')
            ->join('ab.auctionWork', 'aw')->addSelect('aw')
            ->join('aw.work', 'w')->addSelect('w')
            ->join('w.author', 'at')->addSelect('at')
            ->join('aw.auction', 'a')->addSelect('a')
            ->join('ab.currency', 'c')->addSelect('c')
            ->join('a.langs', 'al')->addSelect('al.title')
            ->join('al.lang', 'l')
            ->where('ab.author = :user')
            ->andWhere('l.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale())
            ->orderBy('ab.id', 'desc')
            ->setParameter(':user', $user);

        $bids->getQuery()->getResult();

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $bids,
            $request->query->getInt('page', 1),
            10
        );

        return array(
            'pagination' => $pagination,
        );
    }

    /**
     * @Route("/bids/payment/{id}", name="user_auctions_bid_payment")
     * @Template()
     */
    public function bidProvisionAction($id)
    {

        $em = $this->getDoctrine()->getManager();

        $bid = $em->getRepository('AppBundle:AuctionBid')->createQueryBuilder('b')
            ->join('b.auctionWork', 'aw')->addSelect('aw')
            ->join('aw.auction', 'a')->addSelect('a')
            ->where('b.author = :author')
            ->andWhere('b.id = :id')
            ->setParameter(':author', $this->getUser())
            ->setParameter(':id', $id)
            ->getQuery()->getResult();
        if(!$bid) {
            $bid = $em->getRepository('AppBundle:WorkBid')->createQueryBuilder('b')
                ->join('b.work', 'w')->addSelect('w')
                ->where('b.author = :author')
                ->andWhere('b.id = :id')
                ->setParameter(':author', $this->getUser())
                ->setParameter(':id', $id)
                ->getQuery()->getResult();
        } else {
            $bid = $bid[0];
            $auctionWork = $bid->getAuctionWork();
            $auction = $auctionWork->getAuction();
            $lastBidAuthorId = $auctionWork->getBids()->last()->getAuthor()->getId();
            if($lastBidAuthorId !== $this->getUser()->getId())
            {
                throw $this->createNotFoundException();
            }

            return array(
                'auctionWork' => $auctionWork ,
                'auction' => $auction,
                'bid' => $bid
            );
        }

        if(!$bid) {
            throw $this->createNotFoundException();
        } else {
            $bid = $bid[0];
        }


        return array(
            'bid' => $bid,
            'auctionWork' => '' ,
            'auction' => '',
        );
    }

}
