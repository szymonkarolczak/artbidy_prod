<?php
namespace AppBundle\Controller;

use AppBundle\Entity\AuctionBid;
use AppBundle\Entity\HouseAuctionLang;
use AppBundle\Entity\HouseAuctionObserve;
use AppBundle\Form\HouseAuctionType;
use AppBundle\Entity\HouseAuction;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use AppBundle\Form\HouseAuctionWorkType;
use AppBundle\Form\HouseAuctionResultType;
use Symfony\Component\Intl\Intl;

class HouseAuctionController extends Controller
{

    /**
     * @Route("/auction-houses", name="auction_houses")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $no_user_view = $em->getRepository('AppBundle:Settings')->createQueryBuilder('s')
            ->select('s.value')
            ->where('s.name = :name')
            ->setParameter(':name', 'security_user_logged_view')
            ->getQuery()->getSingleScalarResult();

        if($no_user_view === 'TAK' && !$this->getUser() && $request->query->getInt('page', 1) > 1)
            return $this->render('AppBundle::loggedEx.html.twig');

        $em = $this->getDoctrine()->getManager();

        $qb = $em->getRepository('UserBundle:User')->createQueryBuilder('u');
        $query = $qb->select('u')
            ->where($qb->expr()->like('u.roles', ':role'))
            ->andWhere('u.enabled = :enabled')
            ->setParameter(':enabled', true)
            ->setParameter(':role', '%ROLE_DOM_AUKCYJNY%')
            ->orderBy('u.id', 'DESC');
        $popularQuery = clone $query;

        //Popularne domy aukcyjne
        $popular = $popularQuery->setMaxResults(12)
            ->andWhere('u.pinned = :pinned')
            ->setParameter(':pinned', true)
            ->getQuery()
            ->getResult();

        $endedAuctions = $em->getRepository('AppBundle:HouseAuction')->createQueryBuilder('ha')
            ->join('ha.langs', 'hl')->addSelect('hl.title, hl.description')
            ->join('hl.lang', 'l')
            ->where('ha.approved = :approved')
            ->andWhere('ha.startDate < :date')
            ->andWhere('l.shortcut = :shortcut')
            ->orderBy('ha.startDate')
            ->setMaxResults(12)
            ->setParameter(':shortcut', $request->getLocale())
            ->setParameter(':approved', true)
            ->setParameter(':date', new \DateTime())
            ->getQuery()->getResult();

        $auctions = $em->getRepository('AppBundle:HouseAuction')->createQueryBuilder('ha')
            ->join('ha.langs', 'hl')->addSelect('hl.title, hl.description')
            ->join('hl.lang', 'l')
            ->join('ha.author', 'u')->addSelect('u')
            ->where('ha.approved = :approved')
            ->andWhere('ha.startDate > :date')
            ->andWhere('l.shortcut = :shortcut')
            ->orderBy('ha.startDate')
            ->setMaxResults(12)
            ->setParameter(':shortcut', $request->getLocale())
            ->setParameter(':approved', true)
            ->setParameter(':date', new \DateTime())
            ->getQuery()->getResult();

        $filters = $request->get('_filter');
        if(isset($filters['fullname']) && !empty($filters['fullname']))
        {
            $query->andWhere($qb->expr()->like('u.fullname', ':fullname'));
            $query->setParameter(':fullname', '%'.$filters['fullname'].'%');
        }
        if(isset($filters['city']) && !empty($filters['city']))
        {
            $query->andWhere('u.city = :city');
            $query->setParameter(':city', $filters['city']);
        }
        if(isset($filters['country']) && !empty($filters['country']))
        {
            $query->andWhere('u.country = :country');
            $query->setParameter(':country', $filters['country']);
        }

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            25
        );

        $adsQb = $em->getRepository('AppBundle:Advert')->createQueryBuilder('a');
        $ads = $adsQb->select('l.content')
            ->join('a.langs', 'l')
            ->join('l.lang', 'la')
            ->where($adsQb->expr()->in('a.id', array(6)))
            ->andWhere('la.shortcut = :lang')
            ->setParameter(':lang', $request->getSession()->get('_locale'))
            ->getQuery()->getResult();

//        $countries = Intl::getRegionBundle()->getCountryNames();
//        $countries = array_combine($countries, $countries);
        $countries = $this->countriesAction();
        
        return array(
            'pagination' => $pagination,
            'popular' => $popular,
            'auctions' => $auctions,
            'endedAuctions' => $endedAuctions,
            'ads' => $ads,
            'countries' => $countries
        );
    }

    public function countriesAction() {
        $em = $this->getDoctrine()->getManager();

        $qb = $em->getRepository('UserBundle:User')->createQueryBuilder('u');

        $query = $qb->select('u.country')
            ->distinct()
            ->where($qb->expr()->like('u.roles', ':role'))
            ->andWhere('u.enabled = :enabled')
            ->setParameter(':enabled', true)
            ->setParameter(':role', '%ROLE_DOM_AUKCYJNY%')
            ->orderBy('u.country', 'ASC');
        $query = $query->getQuery()->getResult();
        array_unique($query, SORT_REGULAR);
        
        return $query;
    }
    
    /**
     * @Route("/houseauction/observe/{id}", name="houseauction_observe", requirements={
     *  "id": "\d+"
     * })
     */
    public function observeAction($id, Request $request)
    {
        $lastRoute = $this->get('session')->get('last_route');

        $user = $this->getUser();
        if(!$user)
        {
            $this->addFlash('error', $this->get('translator')->trans('obserwuj.musisz_byc_zalogowany'));
            if (!empty($lastRoute) && !empty($lastRoute['name'])) {
                return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
            } else {
                return new RedirectResponse(($this->generateUrl('homepage')));
            }
        }

        $now = new \DateTime('now');
        if(!$user->getAnnoucement() || $user->getAnnoucement() < $now)
        {
            $this->addFlash('error', $this->get('translator')->trans('obserwuj.kup_usluge'));
            return new \Symfony\Component\HttpFoundation\RedirectResponse($this->generateUrl('footer_information', array(
                'page' => 'annoucements'
            )));
        }

        $em = $this->getDoctrine()->getManager();
        $houseAuction = $em->getRepository('AppBundle:HouseAuction')->find($id);
        if(!$houseAuction)
            throw $this->createNotFoundException ();

        $observe = $em->getRepository('AppBundle:HouseAuctionObserve')->findBy(array(
            'auction' => $houseAuction,
            'user' => $user
        ));
        if($observe)
        {
            $this->addFlash('error', $this->get('translator')->trans('obserwuj.juz_obserwujesz'));
            if (!empty($lastRoute) && !empty($lastRoute['name'])) {
                return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
            } else {
                return new RedirectResponse(($this->generateUrl('homepage')));
            }
        }

        $observe = new HouseAuctionObserve();
        $observe->setAuction($houseAuction);
        $observe->setUser($user);

        $em->persist($observe);
        $em->flush();

        $this->addFlash('success', $this->get('translator')->trans('obserwuj.dodano'));
        return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
    }

    /**
     * @Route("/auction/add", name="auction_add")
     * @Template()
     */
    public function addAction(Request $request)
    {
        if(!($user = $this->getUser()))
            throw $this->createAccessDeniedException();

        $lastRoute = $this->get('session')->get('last_route');
        
        //Sprawdźmy czy wprowadzone zostały wyniki zakończonych aukcji
        $em = $this->getDoctrine()->getManager();

        $lang = $em->getRepository('AppBundle:Language')->findOneBy(array(
            'shortcut' => $request->getLocale()
        ));
        $Alang = new HouseAuctionLang();
        $Alang->setLang($lang);

        $auction = new HouseAuction();
        $auction->setAuthor($this->getUser());
        $auction->addLang($Alang);

        $form = $this->createForm(HouseAuctionType::class, $auction);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            try {

                foreach($auction->getLangs() as $lang)
                    $lang->setAuction($auction);

                $file = $auction->getImage();
                if($file instanceof UploadedFile)
                {
                    $fileName = $this->get('app.uploader')->upload($file, $this->getParameter('houseauction_files_directory'));
                    $auction->setImage($fileName);
                }

                $em->persist($auction);
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('auctionhouse.aukcja.dodane'));
                if (!empty($lastRoute) && !empty($lastRoute['name'])) {
                    return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
                } else {
                    return new RedirectResponse(($this->generateUrl('homepage')));
                }
            } catch(\Exception $e)
            {
                $this->addFlash('error', $this->get('translator')->trans('auctionhouse.aukcja.nie_dodane'));
            }
        }
        
        return array(
            'form' => $form->createView()
        );
    }
    
    /**
     * @Route("/auction/{id}/add", name="houseauction_add_work", requirements={
     *  "id": "\d+"
     * })
     * @Template()
     */
    public function addWorkAction($id, Request $request)
    {
        if(!$this->isGranted('ROLE_DOM_AUKCYJNY') && !$this->isGranted('ROLE_ADMIN'))
            throw $this->createAccessDeniedException();
        
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        
        $houseAuction = $em->getRepository('AppBundle:HouseAuction')->createQueryBuilder('ha')
            ->join('ha.langs', 'hl')->addSelect('hl.title, hl.description')
            ->join('hl.lang', 'l')
            ->where('ha.id = :id')
            ->andWhere('l.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale())
            ->setParameter(':id', $id)
            ->getQuery()->getResult();

        if(!$houseAuction || $houseAuction[0][0]->getAuthor()->getId() != $user->getId())
            throw $this->createNotFoundException ();
        $houseAuction = $houseAuction[0];
        
        $auctionWork = new \AppBundle\Entity\HouseAuctionWork();
        $auctionWork->setAuction($houseAuction[0]);
        $form = $this->createForm(HouseAuctionWorkType::class, $auctionWork, array(
            'user' => $this->getUser()
        ));

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            try
            {
                $em->persist($auctionWork);
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('auctionhouse.obiekt_dodany'));

                $usersObserving = $em->getRepository('AppBundle:HouseAuctionObserve')->createQueryBuilder('po')
                    ->join('po.user', 'u')->addSelect('u')
                    ->where('po.auction = :auction')
                    ->setParameter(':auction', $houseAuction[0])
                    ->getQuery()->getResult();
                foreach($usersObserving as $obUser)
                {
                    $message = \Swift_Message::newInstance()
                        ->setSubject($this->get('translator')->trans('notifications.houseauction_obiekt'))
                        ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
                        ->setTo($obUser->getUser()->getEmail())
                        ->setBody(
                            $this->renderView(
                                '@App/Emails/notification_houseauction_obiekt.html.twig',
                                array(
                                    'work' => $auctionWork,
                                    'user' => $obUser->getUser(),
                                    'target' => $houseAuction[0],
                                    'lang' => $obUser->getUser()->getCountry() == 'Polska' || $obUser->getUser()->getCountry() == 'Poland' ? 'pl' : 'en'
                                )
                            ),
                            'text/html'
                        )
                    ;
                    $this->get('mailer')->send($message);
                }

                return new RedirectResponse($this->generateUrl('houseauction', array(
                    'id' => $houseAuction[0]->getId(),
                    'slug' => $this->get('slugify')->slugify($houseAuction['title'])
                )));
            }
            catch(\Exception $e)
            {
                $this->addFlash('error', $this->get('translator')->trans('auctions.obiekt.nie_dodane'));
            }
        }
        
        return array(
            'auction' => $houseAuction,
            'form' => $form->createView()
        );
    }
    
    /**
     * @Route("/auction/{id}/setResult", name="houseauction_setresult", requirements={
     *  "id": "\d+"
     * })
     * @Template()
     */
    public function setResultAction($id, Request $request)
    {
        if(!$this->isGranted('ROLE_DOM_AUKCYJNY') && !$this->isGranted('ROLE_ADMIN'))
            throw $this->createAccessDeniedException();
        
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $houseAuction = $em->getRepository('AppBundle:HouseAuction')->createQueryBuilder('ha')
            ->join('ha.langs', 'hl')->addSelect('hl.title, hl.description')
            ->join('hl.lang', 'l')
            ->where('ha.id = :id')
            ->andWhere('l.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale())
            ->setParameter(':id', $id)
            ->getQuery()->getResult();

        if(!$houseAuction || $houseAuction[0][0]->getAuthor()->getId() != $user->getId())
            throw $this->createNotFoundException ();

        $houseAuction = $houseAuction[0];

        if($houseAuction[0]->getStartDate()->diff(new \DateTime('now'))->invert)
        {
            $this->addFlash('error', $this->get('translator')->trans('auctionhouse.wyniki_za_wczesnie'));
            $lastRoute = $this->get('session')->get('last_route');
            if (!empty($lastRoute) && !empty($lastRoute['name'])) {
                return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
            } else {
                return new RedirectResponse(($this->generateUrl('homepage')));
            }
        }

        if($houseAuction[0]->getStatus() === 0)
        {
            $work = $request->request->get('work');
            if($work && !empty($work))
            {
                foreach($work as $index => $value)
                {
                    $auctionWork = $em->getRepository('AppBundle:HouseAuctionWork')->find($index);
                    if($auctionWork)
                        $auctionWork->setSoldPrice($value);

                    $em->persist($auctionWork);
                }
                $houseAuction[0]->setStatus(1);
                $em->persist($houseAuction[0]);
                $em->flush();
            }
        }

        return array(
            'auction' => $houseAuction
        );
    }

    /**
     * @Route("/auctions/user", name="user_auctions")
     * @Template()
     */
    public function userAuctionsAction(Request $request)
    {
        if(!$this->isGranted('ROLE_DOM_AUKCYJNY') && !$this->isGranted('ROLE_ADMIN'))
            throw $this->createAccessDeniedException();

        $em = $this->getDoctrine()->getManager();

        $auctions = $em->getRepository('AppBundle:HouseAuction')->createQueryBuilder('ha')
            ->join('ha.langs', 'hl')->addSelect('hl.title, hl.description')
            ->join('hl.lang', 'l')
            ->where('ha.author = :author')
            ->andWhere('l.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale())
            ->setParameter(':author', $this->getUser());

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $auctions,
            $request->query->getInt('page', 1),
            25
        );

        return array(
            'pagination' => $pagination
        );
    }

}
