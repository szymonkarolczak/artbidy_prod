<?php

namespace AdminBundle\Controller;

use AppBundle\Form\HouseAuctionType;
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
 * @Route("/admin/auctions")
 */
class AuctionsController extends Controller
{
    /**
     * @Route("/", name="admin_auctions")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $qb = $em->getRepository('AppBundle:HouseAuction')->createQueryBuilder('a');
        $houses = $qb->join('a.langs', 'al')->addSelect('al.title, al.description')
            ->join('a.author', 'u')->addSelect('u')
            ->join('al.lang', 'l')
            ->where('l.shortcut = :shortcut')
            ->andWhere('a.approved = :approved')
            ->setParameter(':approved', true)
            ->setParameter(':shortcut', $request->getLocale())
            ->getQuery()
            ->getResult();

        return array(
            'houses' => $houses,
            'title' => $this->get('translator')->trans('aukcje.aukcje_rozpoczete', [], 'admin')
        );
    }

    /**
     * @Route("/toAccept", name="admin_auctions_to_accept")
     * @Template()
     */
    public function toAcceptAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $auctions = $em->getRepository('AppBundle:HouseAuction')->createQueryBuilder('ha')
            ->join('ha.langs', 'la')->addSelect('la.title, la.description')
            ->join('la.lang', 'l')
            ->where('ha.approved = :approved')
            ->andWhere('l.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale())
            ->setParameter(':approved', false);

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
     * @Route("/house/accept/{id}", name="admin_auctions_house_accept", requirements={
     *  "id": "\d+"
     * })
     */
    public function acceptHouseAuctionAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $auction = $em->getRepository('AppBundle:HouseAuction')->find($id);
        if (!$auction)
            throw $this->createNotFoundException();

        $auction->setApproved(true);
        $em->persist($auction);
        $em->flush();

//        $this->get('app.houseauction_manager')->createAuctionEvent($auction);

        $this->addFlash('success', $this->get('translator')->trans('aukcje.zaakceptowane', [], 'admin'));
        return new RedirectResponse($this->generateUrl('admin_auctions_to_accept'));
    }

    /**
     * @Route("/house/edit/{id}", name="admin_auctions_house_edit", requirements={
     *  "id": "\d+"
     * })
     * @Template()
     */
    public function editHouseAuctionAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $auction = $em->getRepository('AppBundle:HouseAuction')->find($id);
        if (!$auction)
            throw $this->createNotFoundException();

        $image = $auction->getImage();
        if ($image && file_exists($this->getParameter('houseauction_files_directory') . DIRECTORY_SEPARATOR . $image)) {
            $auction->setImage(new File($this->getParameter('houseauction_files_directory') . DIRECTORY_SEPARATOR . $image));
        }

        $form = $this->createForm(HouseAuctionType::class, $auction, array(
            'admin' => true
        ));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {

                foreach ($auction->getLangs() as $lang)
                    $lang->setAuction($auction);

                $file = $auction->getImage();
                if ($file instanceof UploadedFile) {
                    $fileName = $this->get('app.uploader')->upload($file, $this->getParameter('houseauction_files_directory'));
                    $auction->setImage($fileName);
                } else {
                    $auction->setImage($image);
                }

                $em->persist($auction);
                $em->flush();
                $this->addFlash('success', 'Aukcja została pomyślnie zmieniona.');
                return new RedirectResponse($this->generateUrl('admin_auctions_house_edit', array(
                    'id' => $id
                )));
            } catch (\Exception $e) {
                $this->addFlash('error', 'Nie udało się zmienić danych o aukcji');
            }
        }

        return array(
            'auction' => $auction,
            'form' => $form->createView()
        );
    }

    /**
     * @Route("/house/delete/{id}", name="admin_auctions_house_delete", requirements={
     *  "id": "\d+"
     * })
     */
    public function deleteHouseAuctionAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $auction = $em->getRepository('AppBundle:HouseAuction')->find($id);
        if (!$auction)
            throw $this->createNotFoundException();

        $auction->setApproved(true);
        $em->remove($auction);
        $em->flush();

        $this->addFlash('success', $this->get('translator')->trans('aukcje.usunieta', [], 'admin'));
        return new RedirectResponse($this->generateUrl('admin_auctions_to_accept'));
    }

    /**
     * @Route("/results", name="admin_auctions_results")
     * @Template()
     */
    public function acceptResultsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $query = $em->getRepository('AppBundle:HouseAuction')->createQueryBuilder('ha')
            ->select('ha')
            ->join('ha.langs', 'hl')->addSelect('hl.title, hl.description')
            ->join('hl.lang', 'l')
            ->join('ha.works', 'w')
            ->where('ha.status = 1')
            ->andWhere('l.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale());

        $paginator = $this->get('knp_paginator');
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
     * @Route("/results/see/{id}", name="admin_auctions_results_see")
     * @Template()
     */
    public function acceptResultsSeeAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $auction = $em->getRepository('AppBundle:HouseAuction')->createQueryBuilder('ha')
            ->select('ha')
            ->addSelect('ha_w')
            ->addSelect('w')
            ->addSelect('c')
            ->join('ha.works', 'ha_w')
            ->join('ha_w.work', 'w')
            ->join('w.currency', 'c')
            ->where('ha.id = :id')
            ->setParameter(':id', $id)
            ->getQuery()->getSingleResult();
        if (!$auction)
            throw $this->createNotFoundException();

        return array(
            'auction' => $auction
        );
    }

    /**
     * @Route("/results/accept/{id}", name="admin_auctions_results_accept")
     */
    public function acceptResultAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $houseAuction = $em->getRepository('AppBundle:HouseAuction')->find($id);
        if (!$houseAuction)
            throw $this->createNotFoundException();

        $houseAuction->setStatus(2);
        $em->persist($houseAuction);
        $em->flush();
        $this->addFlash('success', 'Wyniki aukcji zostały zaakceptowane.');
        return new RedirectResponse($this->generateUrl('admin_auctions_results'));
    }

    /**
     * @Route("/results/accept/{id}", name="admin_auctions_results_reject")
     */
    public function rejectResultAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $houseAuction = $em->getRepository('AppBundle:HouseAuction')->find($id);
        if (!$houseAuction)
            throw $this->createNotFoundException();

        $houseAuction->setStatus(0);
        $em->persist($houseAuction);
        $em->flush();
        $this->addFlash('success', 'Wyniki aukcji zostały odrzucone.');
        return new RedirectResponse($this->generateUrl('admin_auctions_results'));
    }

    /**
     * @Route("/results/show/houses", name="admin_auctions_results_show_houses")
     * @Template()
     */
    public function resultsHousesShowAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $auctionWorks = $em->getRepository('AppBundle:HouseAuctionWork')->createQueryBuilder('haw')
            ->join('haw.auction', 'a')->addSelect('a')
            ->join('haw.work', 'w')->addSelect('w')
            ->join('w.author', 'u')->addSelect('u')
            ->where('a.status = :status')
            ->setParameter(':status', 2);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $auctionWorks,
            $request->query->getInt('page', 1),
            25
        );

        return array(
            'pagination' => $pagination
        );
    }

    /**
     * @Route("/results/markPaid/{id}", name="admin_auctions_results_paid")
     */
    public function markAsPaidAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $auctionWork = $em->getRepository('AppBundle:AuctionWork')->find($id);
        if (!$auctionWork)
            throw $this->createNotFoundException();

        $auctionWork->setProvisionPaid(true);
        $em->persist($auctionWork);
        $em->flush();

        $bid = $auctionWork->getBids()->last();

        $this->addFlash('success', 'Zapisano płatność prowizji dla licytacji.');

        $message = \Swift_Message::newInstance()
            ->setSubject($this->get('translator')->trans('mail.auction.prowizja_temat'))
            ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
            ->setTo($bid->getAuthor()->getEmail())
            ->setBody($this->renderView('@App/Emails/auction_provision_buyer.html.twig', array(
                'work' => $auctionWork->getWork(),
                'auction' => $auctionWork->getAuction(),
                'bid' => $bid
            )), 'text/html');
        $this->get('mailer')->send($message);

        $message = \Swift_Message::newInstance()
            ->setSubject($this->get('translator')->trans('mail.auction.prowizja_temat_seller', array(
                '%work%' => $auctionWork->getWork()->getTitle()
            )))
            ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
            ->setTo($auctionWork->getWork()->getAuthor()->getEmail())
            ->setBody($this->renderView('@App/Emails/auction_provision_seller.html.twig', array(
                'work' => $auctionWork->getWork(),
                'auction' => $auctionWork->getAuction(),
                'bid' => $bid
            )), 'text/html');
        $this->get('mailer')->send($message);

        $lastRoute = $this->get('session')->get('last_route');
        if (!empty($lastRoute) && !empty($lastRoute['name'])) {
            return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
        } else {
            return new RedirectResponse(($this->generateUrl('homepage')));
        }
    }


    /**
     * @Route("/results/remindePaid/{id}", name="admin_auctions_results_reminde_paid")
     */
    public function remindePaidAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $auctionWork = $em->getRepository('AppBundle:AuctionWork')->find($id);
        if (!$auctionWork)
            throw $this->createNotFoundException();

        $bid = $auctionWork->getBids()->last();

        $this->addFlash('success', 'Wysłano!');

        $message = \Swift_Message::newInstance()
            ->setSubject($this->get('translator')->trans('mail.pay.remember', array(
                '%work%' => $auctionWork->getWork()->getTitle()
            )))
            ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
            ->setTo($bid->getAuthor()->getEmail())
            ->setBody($this->renderView('@App/Emails/payment_reminders.html.twig', array(
                'work' => $auctionWork->getWork(),
                'auction' => $auctionWork->getAuction(),
                'bid' => $bid,
                'lang' => $request->getLocale(),
            )), 'text/html');
        $this->get('mailer')->send($message);

        return new RedirectResponse($this->generateUrl('admin_fineartsauctions_results'));
    }

    /**
     * @Route("/results/cancelPaid/{id}", name="admin_auctions_results_cancel_paid")
     */
    public function cancelPaidAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $auctionWork = $em->getRepository('AppBundle:AuctionWork')->find($id);
        if (!$auctionWork)
            throw $this->createNotFoundException();

        $bid = $auctionWork->getBids()->last();
        $em->remove($bid);
        $em->flush();

        return new RedirectResponse($this->generateUrl('admin_fineartsauctions_results'));
    }


}
