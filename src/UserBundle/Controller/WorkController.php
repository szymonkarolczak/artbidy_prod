<?php

namespace UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/user/works")
 */
class WorkController extends Controller
{

    /**
     * @Route("/bids", name="user_works_bids")
     * @Template()
     */
    public function bidsAction(Request $request)
    {
        if (!($user = $this->getUser()))
            throw $this->createNotFoundException();

        $em = $this->getDoctrine()->getManager();

        $workBids = $em->getRepository('AppBundle:WorkBid')->createQueryBuilder('wb')
            ->join('wb.work', 'w')->addSelect('w')
            ->join('w.author', 'at')->addSelect('at')
            ->join('wb.currency', 'c')->addSelect('c')
            ->where('wb.author = :user')
            ->orderBy('wb.id', 'desc')
            ->setParameter(':user', $user);

        $workBids->getQuery()->getResult();

        $paginator = $this->get('knp_paginator');

        $pagination = $paginator->paginate(
            $workBids,
            $request->query->getInt('page', 1),
            10
        );


        return array(
            'pagination' => $pagination,
        );
    }

    /**
     * @Route("/bids/payment/{id}", name="user_works_bid_payment")
     * @Template()
     */
    public function bidProvisionAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $bid = $em->getRepository('AppBundle:WorkBid')->createQueryBuilder('b')
            ->join('b.work', 'w')->addSelect('w')
            ->where('b.author = :author')
            ->andWhere('b.id = :id')
            ->setParameter(':author', $this->getUser())
            ->setParameter(':id', $id)
            ->getQuery()->getResult();

        if (!$bid) {
            throw $this->createNotFoundException();
        } else {
            $bid = $bid[0];
        }


        return array(
            'bid' => $bid,
            'auctionWork' => '',
            'auction' => '',
        );
    }

}
