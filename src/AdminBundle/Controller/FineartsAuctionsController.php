<?php

namespace AdminBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;

use AppBundle\Entity\Auction;
use AdminBundle\Form\AuctionType;
use AdminBundle\Form\AuctionWorkType;

/**
 * @Route("/admin/fineartsauctions")
 */
class FineartsAuctionsController extends Controller
{
    /**
     * @Route("/", name="admin_fineartsauctions")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        
        $qb = $em->getRepository('AppBundle:Auction')->createQueryBuilder('a');
        $auctions = $qb->select('a')
            ->join('a.langs', 'l')->addSelect('l.title, l.description')
            ->join('l.lang', 'lg')
            ->orderBy('a.endDate')
            ->groupBy('l.auction');

        if($type = $request->get('type'))
        {
            if($type == 'ended')
            {
                $auctions->where('a.endDate < :now_date')
                    ->setParameter(':now_date', new \DateTime());
            } else {
                $auctions->where('a.startDate > :now_date')
                    ->setParameter(':now_date', new \DateTime());
            }
        }
        else {
            $auctions->where('a.startDate < :now_date')
                ->andWhere('a.endDate > :now_date')
                ->setParameter(':now_date', new \DateTime());
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $auctions,
            $request->query->getInt('page', 1),
            25
        );
        
        return array(
            'pagination' => $pagination
        );
    }

    /**
     * @Route("/{id}/offers", name="admin_fineartsauctions_works_offers", requirements={
     *  "id": "\d+"
     * })
     * @Template()
     */
    public function workOffersAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $auctionWork = $em->getRepository('AppBundle:AuctionWork')->find($id);
        if(!$auctionWork)
            throw $this->createNotFoundException();

        $query = $em->getRepository('AppBundle:AuctionBid')->createQueryBuilder('ab')
            ->where('ab.auctionWork = :auctionWork')
            ->setParameter(':auctionWork', $auctionWork);

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            25
        );

        return array(
            'pagination' => $pagination,
            'auctionWork' => $auctionWork
        );
    }

    /**
     * @Route("/{id}/works", name="admin_fineartsauctions_works", requirements={
     *  "id": "\d+"
     * })
     * @Template()
     */
    public function worksAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $auction = $em->getRepository('AppBundle:Auction')->createQueryBuilder('a')
            ->join('a.langs', 'l')->addSelect('l.title, l.description')
            ->join('l.lang', 'lg')
            ->where('a.id = :id')
            ->setParameter(':id', $id)
            ->groupBy('l.auction')
            ->getQuery()
            ->getSingleResult();

        if(!$auction)
            throw $this->createNotFoundException();

        $qb = $em->getRepository('AppBundle:AuctionWork')->createQueryBuilder('aw');
        $query = $qb->select('aw, w, a')
            ->join('aw.work', 'w')
            ->join('w.author', 'a')
            ->where('aw.auction = :auction')
            ->orderBy('aw.id', 'DESC')
            ->setParameter(':auction', $auction[0]);

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            25
        );

        return array(
            'pagination' => $pagination,
            'auction' => $auction
        );
    }

    /**
     * @Route("/{id}/works/delete/{work_id}", name="admin_fineartsauctions_works_delete", requirements={
     *  "id": "\d+",
     *  "work_id": "\d+"
     * })
     */
    public function deleteWorkAction($id, $work_id)
    {
        $em = $this->getDoctrine()->getManager();

        $auction = $em->getRepository('AppBundle:Auction')->find($id);
        if(!$auction)
            throw $this->createNotFoundException();

        $auctionWork = $em->getRepository('AppBundle:AuctionWork')->createQueryBuilder('aw')
            ->select('aw')
            ->where('aw.auction = :auction')
            ->andWhere('aw.id = :id')
            ->setParameter(':auction', $auction)
            ->setParameter(':id', $work_id)
            ->getQuery()->getSingleResult();
        if(!$auctionWork)
            throw $this->createNotFoundException();

        try {
            $em->remove($auctionWork);
            $em->flush();

            $message = \Swift_Message::newInstance()
                ->setSubject($this->get('translator')->trans('mail.aukcja.dzielo_usuniete', [], 'admin'))
                ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
                ->setTo($auctionWork->getWork()->getAuthor()->getEmail())
                ->setBody(
                    $this->renderView('AdminBundle:Emails:auction_work_deleted.html.twig', array(
                        'work' => $auctionWork->getWork(),
                        'author' => $auctionWork->getWork()->getAuthor(),
                        'auction' => $auction
                    )),
                    'text/html'
                );
            $this->get('mailer')->send($message);

            $this->addFlash('success', $this->get('translator')->trans('aukcje.dziela.usuniete', [], 'admin'));
        } catch(\Exception $e)
        {
            $this->addFlash('error', $this->get('translator')->trans('aukcje.dziela.nie_usuniete', [], 'admin') . $e->getMessage());
        }

        return new RedirectResponse($this->generateUrl('admin_fineartsauctions_works', array('id' => $auction->getId())));
    }

    /**
     * @Route("/{id}/works/accept/{work_id}", name="admin_fineartsauctions_works_edit", requirements={
     *  "id": "\d+",
     *  "work_id": "\d+"
     * })
     * @Template()
     */
    public function editWorkAction($id, $work_id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $auction = $em->getRepository('AppBundle:Auction')->find($id);
        if(!$auction)
            throw $this->createNotFoundException();

        $auctionWork = $em->getRepository('AppBundle:AuctionWork')->createQueryBuilder('aw')
            ->select('aw')
            ->join('aw.work', 'w')
            ->where('aw.auction = :auction')
            ->andWhere('aw.id = :id')
            ->setParameter(':auction', $auction)
            ->setParameter(':id', $work_id)
            ->getQuery()->getSingleResult();
        if(!$auctionWork)
            throw $this->createNotFoundException();

        $form = $this->createForm(AuctionWorkType::class, $auctionWork);
        $form->handleRequest($request);

        if($form->isValid() && $form->isSubmitted())
        {
            if($auctionWork->getApproved())
                $auctionWork->setApprovedBy($this->getUser());

            try {
                $em->persist($auctionWork);
                $em->flush();

                if($auctionWork->getApproved())
                {
                    $message = \Swift_Message::newInstance()
                        ->setSubject($this->get('translator')->trans('mail.aukcja.dzielo_zaakceptowane', [], 'admin'))
                        ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
                        ->setTo($auctionWork->getWork()->getAuthor()->getEmail())
                        ->setBody(
                            $this->renderView('AdminBundle:Emails:auction_work_accepted.html.twig', array(
                                'work' => $auctionWork->getWork(),
                                'author' => $auctionWork->getWork()->getAuthor(),
                                'auction' => $auction,
                                'lang' => $auctionWork->getWork()->getAuthor()->getCountry() == 'Polska' || $auctionWork->getWork()->getAuthor()->getCountry() == 'Poland' ? 'pl' : 'en'
                            )),
                            'text/html'
                        );
                    $this->get('mailer')->send($message);

                    if($auctionWork->getApproved())
                    {
                        $usersObserving = $em->getRepository('AppBundle:ProfileObserve')->createQueryBuilder('po')
                            ->join('po.user', 'u')->addSelect('u')
                            ->where('po.targetUser = :user')
                            ->setParameter(':user', $auctionWork->getWork()->getAuthor())
                            ->getQuery()->getResult();
                        foreach($usersObserving as $obUser)
                        {
                            $message = \Swift_Message::newInstance()
                                ->setSubject($this->get('translator')->trans('notifications.user_aukcja'))
                                ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
                                ->setTo($obUser->getUser()->getEmail())
                                ->setBody(
                                    $this->renderView(
                                        '@App/Emails/notification_user_auction.html.twig',
                                        array(
                                            'work' => $auctionWork,
                                            'user' => $obUser->getUser(),
                                            'target' => $auctionWork->getWork()->getAuthor(),
                                            'lang' => $obUser->getUser()->getCountry() == 'Polska' || $obUser->getUser()->getCountry() == 'Poland' ? 'pl' : 'en'
                                        )
                                    ),
                                    'text/html'
                                )
                            ;
                            $this->get('mailer')->send($message);
                        }

                        $usersObserving = $em->getRepository('AppBundle:FineartsAuctionObserve')->createQueryBuilder('fao')
                            ->join('fao.user', 'u')->addSelect('u')
                            ->where('fao.auction = :auction')
                            ->setParameter(':auction', $auction)
                            ->getQuery()->getResult();
                        foreach($usersObserving as $obUser)
                        {
                            $message = \Swift_Message::newInstance()
                                ->setSubject($this->get('translator')->trans('notifications.aukcja_nowy_obiekt'))
                                ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
                                ->setTo($obUser->getUser()->getEmail())
                                ->setBody(
                                    $this->renderView(
                                        '@App/Emails/notification_auction_work.html.twig',
                                        array(
                                            'work' => $auctionWork,
                                            'user' => $obUser->getUser(),
                                            'target' => $auctionWork->getWork()->getAuthor(),
                                            'lang' => $obUser->getUser()->getCountry() == 'Polska' || $obUser->getUser()->getCountry() == 'Poland' ? 'pl' : 'en'
                                        )
                                    ),
                                    'text/html'
                                )
                            ;
                            $this->get('mailer')->send($message);
                        }
                    }

                    $this->addFlash('success', $this->get('translator')->trans('aukcje.dziela.zaakceptowane', [], 'admin'));
                }
                else
                {
                    $this->addFlash('success', $this->get('translator')->trans('aukcje.dziela.zmienione', [], 'admin'));
                }

            } catch(\Exception $e)
            {
                $this->addFlash('error', $this->get('translator')->trans('aukcje.dziela.nie_zmienione', [], 'admin').$e->getMessage());
            }

        }

        return array(
            'form' => $form->createView(),
            'work' => $auctionWork
        );
    }

    /**
     * @Route("/add", name="admin_fineartsauctions_add")
     */
    public function addAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $auction = new Auction();
        $auction->setIncrement(array(
            '0' => 50,
            '1000' => 100,
            '5000' => 500,
            '10000' => 1000
        ));
        $form = $this->createForm(AuctionType::class, $auction);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            try
            {
                $file = $auction->getImage();
                if($file instanceof UploadedFile)
                {
                    $fileName = $this->get('app.uploader')->upload($file, $this->getParameter('auction_files_directory'));
                    $auction->setImage($fileName);
                }

                foreach($auction->getLangs() as $lang)
                {
                    $lang->setAuction($auction);
                }

                $em->persist($auction);
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('aukcje.dodana', [], 'admin'));
                return new RedirectResponse($this->generateUrl('admin_auctions_edit', array(
                    'id' => $auction->getId()
                )));
            }
            catch(\Exception $e)
            {
                $this->addFlash('error', $this->get('translator')->trans('aukcje.nie_dodana'.$e->getMessage(), [], 'admin'));
            }
        }

        return $this->render('AdminBundle:FineartsAuctions:add_edit.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/edit/{id}", name="admin_auctions_edit", requirements={
     *  "id": "\d+"
     * })
     */
    public function editAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $auction = $em->getRepository('AppBundle:Auction')->find($id);
        if(!$auction)
            throw $this->createNotFoundException();

        $image = $auction->getImage();
        if($image && file_exists($this->getParameter('auction_files_directory').'/'.$image))
            $auction->setImage(
                new File($this->getParameter('auction_files_directory').'/'.$image)
            );
        else $auction->setImage(null);

        $form = $this->createForm(AuctionType::class, $auction);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            try
            {
                $file = $auction->getImage();
                if($file instanceof UploadedFile)
                {
                    $image = $this->get('app.uploader')->upload($file, $this->getParameter('auction_files_directory'));
                }
                $auction->setImage($image);

                $em->persist($auction);
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('aukcje.zmieniona', [], 'admin'));
                return new RedirectResponse($this->generateUrl('admin_auctions_edit', array(
                    'id' => $id
                )));
            }
            catch(\Exception $e)
            {
                $this->addFlash('error', $this->get('translator')->trans('aukcje.nie_zmieniona'.$e->getMessage(), [], 'admin'));
            }
        }

        return $this->render('AdminBundle:FineartsAuctions:add_edit.html.twig', array(
            'form' => $form->createView(),
            'auction' => $auction
        ));
    }

    /**
     * @Route("/delete/{id}", name="admin_auctions_delete", requirements={
     *  "id": "\d+"
     * })
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $auction = $em->getRepository('AppBundle:Auction')->find($id);
        if(!$auction)
            throw $this->createNotFoundException();

        $works = $em->getRepository('AppBundle:AuctionWork')->findBy([
            'auction' => $auction
        ]);
        if(is_array($works))
            foreach($works as $work)
                $em->remove($work);

        $em->remove($auction);
        $em->flush();
        $this->addFlash('success', $this->get('translator')->trans('aukcje.usunieta', [], 'admin'));
        return new RedirectResponse($this->generateUrl('admin_fineartsauctions'));
    }

  /**
     * @Route("/results", name="admin_fineartsauctions_results")
     * @Template()
     */
    public function resultsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $auctionWorks = $em->getRepository('AppBundle:AuctionWork')->createQueryBuilder('aw')
            ->join('aw.bids', 'b')->addSelect('b')
            ->join('aw.work', 'w')->addSelect('w')
            ->join('w.author', 'aut')->addSelect('aut')
            ->join('aw.auction', 'a')->addSelect('a')
            ->join('a.langs', 'l')->addSelect('l.title, l.description')
            ->join('l.lang', 'lg')
            ->join('w.currency', 'c')->addSelect('c')
            ->join('b.author', 'u')->addSelect('u')
            ->where('a.endDate < :date')
//            ->groupBy('b.auctionWork')
            ->setParameter(':date', new \DateTime('now'));

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $auctionWorks,
            $request->query->getInt('page', 1),
            25
        );
//        echo "test2";
        
        return array(
            'pagination' => $pagination
        );
    }

}
