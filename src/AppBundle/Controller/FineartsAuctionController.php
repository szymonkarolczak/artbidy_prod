<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Notice;
use Symfony\Component\Validator\Constraints\DateTime;

use AppBundle\Entity\AuctionBid;
use AppBundle\Entity\AuctionWorkObserve;
use AppBundle\Entity\FineartsAuctionObserve;
use AppBundle\Form\AuctionBidType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Entity\AuctionWork;
use AppBundle\Form\AuctionWorkType;
use AppBundle\Entity\Work;
use AppBundle\Form\WorkType;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @Route("auction")
 */
class FineartsAuctionController extends Controller
{
    /**
     * @Route("s/", name="auctions")
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

        if(strip_tags($no_user_view) !== 'TAK' && !$this->getUser() && $request->query->getInt('page', 1) > 1) {
            return $this->render('AppBundle::loggedEx.html.twig');
        }

        $em = $this->getDoctrine()->getManager();

        $qb = $em->getRepository('AppBundle:Auction')->createQueryBuilder('a');
        $query = $qb->select('a')
            ->join('a.langs', 'l')->addSelect('l.title, l.description,l.metatitle')
            ->join('l.lang', 'lg')
            ->where('a.enabled = :enabled')
            ->andWhere('lg.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale())
            ->setParameter(':enabled', true)
            ->orderBy('a.id', 'DESC');
        $popularQuery = clone $query;
        $ongoingQuery = clone $query;
        $nadchodzaceAukcjeQuery = clone $query;
        $nextQuery = clone $query;
        $endedQuery = clone $query;

        $popular = $popularQuery->andWhere('a.pinned = :pinned')
            ->setParameter(':pinned', true)
            ->andWhere('a.endDate > :date')
            ->setParameter(':date', new \DateTime('now'))
            ->setMaxResults(15)
            ->getQuery()->getResult();

        $ongoing = $ongoingQuery
            ->andWhere('a.startDate < :date')
            ->andWhere('a.endDate > :date')
            ->setParameter(':date', new \DateTime('now'))
            ->setMaxResults(15)
            ->getQuery()->getResult();

        $nadchodzaceAukcje = $nadchodzaceAukcjeQuery
            ->andWhere('a.startDate > :dateStart')
            ->andWhere('a.endDate > :dateEnd')
            ->setParameter(':dateStart', new \DateTime('now'))
            ->setParameter(':dateEnd', new \DateTime('now'))
            ->setMaxResults(15)
            ->getQuery()->getResult();

        $next = $nextQuery->andWhere('a.startDate > :date')
            ->setParameter(':date', new \DateTime('now'))
            ->setMaxResults(15)
            ->getQuery()->getResult();

        $endedauctions = $endedQuery->andWhere('a.endDate < :date')
            ->setParameter(':date', new \DateTime('now'))
            ->getQuery()->getResult();

        $paginator = $this->get('knp_paginator');
        $ended = $paginator->paginate(
            $endedauctions,
            $request->query->getInt('page', 1),
            15
        );


        $bidedOferts = $em->getRepository('AppBundle:AuctionWork')->createQueryBuilder('aw')
            ->leftJoin('aw.bids', 'b')->addSelect('COUNT(b.id) AS bids')
            ->join('aw.work', 'w')->addSelect('w')
            ->join('aw.auction', 'a')->addSelect('a')
            ->join('a.langs', 'l')->addSelect('l.title, l.description,l.metatitle')
            ->join('l.lang', 'lg')
            ->where('aw.approved = :approved')
            ->andWhere('lg.shortcut = :shortcut')
            ->andWhere('a.startDate < :date')
            ->andWhere('a.endDate > :date')
            ->having('COUNT(b.id) > :bids')
            ->orderBy('bids', 'DESC')
            ->groupBy('aw.id')
            ->setMaxResults(6)
            ->setParameter(':bids', 0)
            ->setParameter(':approved', true)
            ->setParameter(':date', new \DateTime())
            ->setParameter(':shortcut', $request->getLocale())
            ->getQuery()->getResult();

        $bidedViews = $em->getRepository('AppBundle:AuctionWork')->createQueryBuilder('aw')
            ->join('aw.work', 'w')->addSelect('w')
            ->join('aw.auction', 'a')->addSelect('a')
            ->join('a.langs', 'l')->addSelect('l.title, l.description,l.metatitle')
            ->join('l.lang', 'lg')
            ->where('aw.approved = :approved')
            ->andWhere('lg.shortcut = :shortcut')
            ->andWhere('a.startDate < :date')
            ->andWhere('a.endDate > :date')
            ->orderBy('aw.views', 'DESC')
            ->setMaxResults(6)
            ->setParameter(':approved', true)
            ->setParameter(':date', new \DateTime())
            ->setParameter(':shortcut', $request->getLocale())
            ->getQuery()->getResult();

        $bidedObserved = $em->getRepository('AppBundle:AuctionWork')->createQueryBuilder('aw')
            ->join('aw.work', 'w')->addSelect('w')
            ->join('aw.observed', 'o')->addSelect('COUNT(o.id) AS observed')
            ->join('aw.auction', 'a')->addSelect('a')
            ->join('a.langs', 'l')->addSelect('l.title, l.description,l.metatitle')
            ->join('l.lang', 'lg')
            ->where('aw.approved = :approved')
            ->andWhere('lg.shortcut = :shortcut')
            ->andWhere('a.startDate < :date')
            ->andWhere('a.endDate > :date')
            ->groupBy('aw.id')
            ->having('COUNT(o.id) > :observed')
            ->setMaxResults(6)
            ->setParameter(':observed', 0)
            ->setParameter(':approved', true)
            ->setParameter(':date', new \DateTime())
            ->setParameter(':shortcut', $request->getLocale())
            ->getQuery()->getResult();

        $filters = $request->get('_filter');
        if (isset($filters['title']) && !empty($filters['title'])) {
            $query->andWhere($qb->expr()->like('l.title', ':title'));
            $query->setParameter(':title', '%' . $filters['title'] . '%');
        }
        if (isset($filters['auctions']) && is_array($filters['auctions']) && !empty($filters['auctions'])) {
            if (in_array('ongoing', $filters['auctions'])) {
//                $query->andWhere('a.endDate > :date AND a.startDate < :date');
            }
            if (in_array('next', $filters['auctions'])) {
//                $query->andWhere('a.startDate > :date');
            }
            if (in_array('ended', $filters['auctions'])) {
//                $query->andWhere('a.endDate < :date');
            }
//            $query->setParameter(':date', new \DateTime('now'));
        }

        if ($sort = $request->get('sort')) {
            list($fieldName, $direction) = explode(',', $sort);
            if ($direction && !in_array($direction, array('DESC', 'ASC')))
                $direction = 'DESC';
            switch ($fieldName) {
                case 'id':
                    $query->orderBy('w.id', $direction);
                    break;
                case 'price':
                    $query->orderBy('w.price', $direction);
                    break;
                case 'add_date':
                    $query->orderBy('w.add_date', $direction);
                    break;
                case 'title':
                    $query->orderBy('w.title', $direction);
                    break;
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            25
        );

        $adsQb = $em->getRepository('AppBundle:Advert')->createQueryBuilder('a');
        $ads = $adsQb->select('l.content')
            ->join('a.langs', 'l')
            ->join('l.lang', 'la')
            ->where($adsQb->expr()->in('a.id', array(7)))
            ->andWhere('la.shortcut = :lang')
            ->setParameter(':lang', $request->getSession()->get('_locale'))
            ->getQuery()->getResult();


        return array(
            'pagination' => $pagination,
            'popular' => $popular,
            'ongoing' => $ongoing,
            'next' => $next,
            'ended' => $ended,
            'ads' => $ads,
            'bidedOferts' => $bidedOferts,
            'bidedViews' => $bidedViews,
            'bidedObserved' => $bidedObserved,
            'nadchodzaceAukcje' => $nadchodzaceAukcje
        );
    }

    /**
     * @Route("observe/{id}", name="auction_observe", requirements={
     *  "id": "\d+"
     * })
     */
    public function observeAction($id, Request $request)
    {
        $lastRoute = $this->get('session')->get('last_route');

        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', $this->get('translator')->trans('obserwuj.musisz_byc_zalogowany'));
            if (!empty($lastRoute) && !empty($lastRoute['name'])) {
                return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
            } else {
                return new RedirectResponse(($this->generateUrl('homepage')));
            }
        }

        $em = $this->getDoctrine()->getManager();
        $auction = $em->getRepository('AppBundle:Auction')->find($id);
        if (!$auction)
            throw $this->createNotFoundException();

        $observe = $em->getRepository('AppBundle:FineartsAuctionObserve')->findBy(array(
            'auction' => $auction,
            'user' => $user
        ));
        if ($observe) {
            $this->addFlash('error', $this->get('translator')->trans('obserwuj.juz_obserwujesz'));
            return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
        }

        $observe = new FineartsAuctionObserve();
        $observe->setAuction($auction);
        $observe->setUser($user);

        $em->persist($observe);
        $em->flush();

        $this->addFlash('success', $this->get('translator')->trans('obserwuj.dodano'));
        if (!empty($lastRoute) && !empty($lastRoute['name'])) {
            return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
        } else {
            return new RedirectResponse(($this->generateUrl('homepage')));
        }
    }

    /**
     * @Route("/{id}-{slug}/add/{work_id}", name="auction_add_work", requirements={
     *  "slug": "^[a-z0-9]+(?:-[a-z0-9]+)*$",
     *  "id": "\d+",
     * "work_id": "\d+"
     * })
     * @Template()
     */
    public function addWorkAction($id, $slug, Request $request, $work_id = 0)
    {
        $em = $this->getDoctrine()->getManager();

        $auction = $em->getRepository('AppBundle:Auction')->createQueryBuilder('a')
            ->join('a.langs', 'l')->addSelect('l.title, l.description,l.metatitle')
            ->join('l.lang', 'lg')
            ->where('a.id = :id')
            ->andWhere('lg.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale())
            ->setParameter(':id', $id)
            ->getQuery()->getSingleResult();
        if (!$auction)
            throw $this->createNotFoundException();

        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED'))
            return array('auction' => $auction);

        $workAvailableForFree = (int)$this->getParameter('auction_free_limit'); //always unlimited


        if ($work_id) {

            $work = $em->getRepository('AppBundle:Work')->find($work_id);
            if (!$work || $work->getAuthor()->getId() != $this->getUser()->getId())
                throw $this->createNotFoundException();

        }


        $auctionWork = new AuctionWork();
        $auctionWork->setAuction($auction[0]);
        if ($work_id)
            $auctionWork->setWork($work);

        $form = $this->createForm(AuctionWorkType::class, $auctionWork, array(
            'user' => $this->getUser(),
            'auction' => $auction[0],
            'minStartPrice' => !empty($auction[0]->getStartPrice()) ? $this->get('app.currency')->convert('PLN', strtoupper($request->getSession()->get('_currency')), $auction[0]->getStartPrice()) : 0,
            'customStartPrice' => $auction[0]->getCustomStartPrice(),
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$auction[0]->getCustomStartPrice()) {
                $startPrice = $auction[0]->getStartPrice() ? $auction[0]->getStartPrice() : 1;
                $auctionWork->setCurrentPrice($startPrice);
                $auctionWork->setStartPrice($this->get('app.currency')->convert('PLN', strtoupper($request->getSession()->get('_currency')), $startPrice));
            }
            $auctionWork->setViews(0);
            try {
                if (!$auctionWork->getCurrentPrice()) {
                    $auctionWork->setCurrentPrice($this->get('app.currency')->convert(strtoupper($request->getSession()->get('_currency')), 'PLN', $auctionWork->getStartPrice()));
                }
                $em->persist($auctionWork);
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('auctions.obiekt.dodane'));
                return new RedirectResponse($this->generateUrl('user_auctions_works'));
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('auctions.obiekt.nie_dodane'));
            }
        }

        return array(
            'auction' => $auction,
            'form' => $form->createView(),
            'work' => isset($work) ? $work : null,
            'workAvailableForFree' => $workAvailableForFree
        );
    }

    /**
     * @Route("/{id}-{slug}", name="auction", requirements={
     *  "slug": "^[a-z0-9]+(?:-[a-z0-9]+)*$",
     *  "id": "\d+"
     * })
     * @Template()
     */
    public function seeAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $no_user_view = $em->getRepository('AppBundle:Settings')->createQueryBuilder('s')
            ->select('s.value')
            ->where('s.name = :name')
            ->setParameter(':name', 'security_user_logged_view')
            ->getQuery()->getSingleScalarResult();

        if($no_user_view !== 'TAK' && !$this->getUser() && $request->query->getInt('page', 1) > 1)
            return $this->render('AppBundle::loggedEx.html.twig');

        $em = $this->getDoctrine()->getManager();

        $auction = $em->getRepository('AppBundle:Auction')->createQueryBuilder('a')
            ->join('a.langs', 'l')->addSelect('l.title, l.description,l.metatitle')
            ->join('l.lang', 'lg')
            ->where('a.id = :id')
            ->andWhere('lg.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale())
            ->setParameter(':id', $id)
            ->getQuery()->getSingleResult();
        if (!$auction)
            throw $this->createNotFoundException();

        $worksQb = $em->getRepository('AppBundle:AuctionWork')->createQueryBuilder('aw');
        $worksQuery = $worksQb->join('aw.work', 'w')->addSelect('w')
            ->leftJoin('UserBundle:User', 'au', 'WITH', 'au.fullname = w.artist and au.roles LIKE :role_a')->addSelect('au.id, au.fullname')
            ->where('aw.approved = :approved')
            ->andWhere('aw.auction = :auction')
            ->setParameter(':approved', true)
            ->setParameter(':role_a', '%ROLE_ARTYSTA%')
            ->setParameter(':auction', $auction[0]);

        if (($sort = $request->get('sort')) and !empty($sort)) {
            list($fieldName, $direction) = explode(',', $sort);
            if ($direction && !in_array($direction, array('DESC', 'ASC')))
                $direction = 'DESC';
            switch ($fieldName) {
                case 'price':
                    $worksQuery->orderBy('aw.currentPrice', $direction);
                    break;
                case 'title':
                    $worksQuery->orderBy('w.title', $direction);
                    break;
                case 'offerts':
                    $worksQuery->orderBy('aw.offersCount', $direction);
                    break;
                case 'views':
                    $worksQuery->orderBy('aw.views', $direction);
                    break;
                case 'observe':
                    $worksQuery->leftJoin('aw.observed', 'o')
                        ->addSelect('COUNT(o.id) AS observed')
                        ->groupBy('aw.id')->orderBy('observed', 'DESC');
                    break;
            }
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $worksQuery,
            $request->query->getInt('page', 1),
            24
        );

        $adsQb = $em->getRepository('AppBundle:Advert')->createQueryBuilder('a');
        $ads = $adsQb->select('l.content')
            ->join('a.langs', 'l')
            ->join('l.lang', 'la')
            ->where($adsQb->expr()->in('a.id', array(11)))
            ->andWhere('la.shortcut = :lang')
            ->setParameter(':lang', $request->getSession()->get('_locale'))
            ->getQuery()->getResult();

        if ($user = $this->getUser()) {
            $observe = $em->getRepository('AppBundle:FineartsAuctionObserve')->findBy(array(
                'auction' => $auction,
                'user' => $user
            ));
        }

        return array(
            'auction' => $auction,
            'pagination' => $pagination,
            'ads' => $ads,
            'observe' => isset($observe) ? $observe : null
        );
    }

    /**
     * @Route("/{id}-{slug}/{work_id}-{work_slug}", name="auction_see_work", requirements={
     *  "slug": "^[a-z0-9]+(?:-[a-z0-9]+)*$",
     *  "id": "\d+",
     *  "work_id": "\d+",
     *  "work_slug": "^[a-z0-9]+(?:-[a-z0-9]+)*$"
     * })
     * @Template()
     */
    public function seeWorkAction($id, $slug, $work_id, $work_slug, Request $request)
    {

        $em = $this->getDoctrine()->getManager();

        $auction = $em->getRepository('AppBundle:Auction')->createQueryBuilder('a')
            ->join('a.langs', 'l')->addSelect('l.title, l.description,l.metatitle')
            ->join('l.lang', 'lg')
            ->where('a.id = :id')
            ->andWhere('lg.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale())
            ->setParameter(':id', $id)
            ->getQuery()->getSingleResult();

        if (!$auction)
            throw $this->createNotFoundException();


        $work = $em->getRepository('AppBundle:AuctionWork')->createQueryBuilder('aw')
            ->where('aw.approved = :approved')
            ->andWhere('aw.auction = :auction')
            ->andWhere('aw.id = :id')
            ->join('aw.work', 'w')->addSelect('w')
            ->setParameter(':id', $work_id)
            ->setParameter(':approved', true)
            ->setParameter(':auction', $auction[0]->getId())->getQuery()->getResult();


        $przebieg = $em->getRepository('AppBundle:AuctionBid')->createQueryBuilder('ab')
            ->join('ab.currency', 'c')->addSelect('c')
            ->where('ab.auctionWork = :work')
            ->setParameter(':work', $work)
            ->orderBy('ab.id', 'DESC')
            ->getQuery()->getResult();

        if (isset($work[0])) {
            $work = $work[0];
        }

        if (!$work)
            throw $this->createNotFoundException();

        $auctionModel = $this->get('app.auction');

        if (!empty($work) && ($work->getBids()->count())) {
            $nextMinPrice = $auctionModel->getMinNextPrice($auction[0]->getIncrement(), $this->get('app.currency')->convert(
                $work->getBids()->last()->getCurrency()->getCode(),
                'PLN',
                $work->getBids()->last()->getAmount()
            ));
            $nextMinPrice = $this->get('app.currency')->convert(
                $work->getBids()->last()->getCurrency()->getCode(),
                $request->getSession()->get('_currency'),
                $nextMinPrice
            );
        } else {
            $nextMinPrice = $work->getStartPrice();
            $nextMinPrice = $this->get('app.currency')->convert(
                $work->getWork()->getCurrency()->getCode(),
                $request->getSession()->get('_currency'),
                $nextMinPrice
            );
        }


        $bid = new AuctionBid();
        $bid->setAuthor($this->getUser());
        $bid->setAuctionWork($work);
        $form = $this->createForm(AuctionBidType::class, $bid, array(
            'minPrice' => $nextMinPrice
        ));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $currency = $em->getRepository('AppBundle:Currency')->findBy(array(
                'code' => $request->getSession()->get('_currency')
            ));
            if (!$currency) throw $this->createAccessDeniedException();
            $bid->setCurrency($currency[0]);

            try {
                $em->persist($bid);
                $em->flush();

                $work->setCurrentPrice($this->get('app.currency')->convert(
                    $bid->getCurrency()->getCode(),
                    'PLN',
                    $bid->getAmount()
                ));
                $work->setOffersCount($work->getOffersCount() + 1);
                $em->persist($work);
                $em->flush();

                $message = \Swift_Message::newInstance()
                    ->setSubject($this->get('translator')->trans('mail.licytacja_podbita_temat'))
                    ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
                    ->setTo($work->getWork()->getAuthor()->getEmail())
                    ->setBody(
                        $this->renderView('AppBundle:Emails:new_bid.html.twig', array(
                            'auction' => $auction,
                            'work' => $work->getWork(),
                            'bid' => $bid,
                            'author' => $work->getWork()->getAuthor()
                        )),
                        'text/html'
                    );


                $this->get('mailer')->send($message);
                $workUserName = $work->getWork()->getAuthor();
                $this->addNotice('licytacja_podbita_temat', $workUserName, $message, $auction[0]);


                $message = \Swift_Message::newInstance()
                    ->setSubject($this->get('translator')->trans('mail.potwierdzenie_zlozenia_oferty'))
                    ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
                    ->setTo($this->getUser()->getEmail())
                    ->setBody(
                        $this->renderView('AppBundle:Emails:bid_confirmation.html.twig', array(
                            'auction' => $auction,
                            'work' => $work->getWork(),
                            'bid' => $bid,
                            'author' => $this->getUser()
                        )),
                        'text/html'
                    );
                $this->get('mailer')->send($message);

                //Emaile o podbiciu oferty
                $users = $em->getRepository('AppBundle:AuctionBid')->createQueryBuilder('ab')
                    ->join('ab.author', 'a')
                    ->where('ab.auctionWork = :auctionWork')
                    ->andWhere('a.email != :email')
                    ->setParameter(':auctionWork', $work)
                    ->setParameter(':email', $this->getUser()->getEmail())
                    ->groupBy('ab.author')
                    ->getQuery()->getResult();
                foreach ($users as $user) {
                    $message = \Swift_Message::newInstance()
                        ->setSubject($this->get('translator')->trans('mail.oferta_podbita'))
                        ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
                        ->setTo($user->getAuthor()->getEmail())
                        ->setBody(
                            $this->renderView('AppBundle:Emails:bid_info.html.twig', array(
                                'auction' => $auction,
                                'work' => $work->getWork(),
                                'bid' => $bid,
                                'author' => $this->getUser()
                            )),
                            'text/html'
                        );
                    $this->get('mailer')->send($message);
                    $this->addNotice('oferta_podbita', $user->getAuthor(), $message, $auction[0]);

                }

                $this->addFlash('success', $this->get('translator')->trans('auctions.obiekt.oferta_zlozona'));
                return new RedirectResponse($this->generateUrl('auction_see_work', array(
                    'id' => $id,
                    'slug' => $slug,
                    'work_id' => $work_id,
                    'work_slug' => $work_slug
                )));

            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('auctions.obiekt.oferta_nie_zlozona'));
            }
        }

        $adsQb = $em->getRepository('AppBundle:Advert')->createQueryBuilder('a');
        $ads = $adsQb->select('l.content')
            ->join('a.langs', 'l')
            ->join('l.lang', 'la')
            ->where($adsQb->expr()->in('a.id', array(13)))
            ->andWhere('la.shortcut = :lang')
            ->setParameter(':lang', $request->getSession()->get('_locale'))
            ->getQuery()->getResult();

        $policy = $em->getRepository('AppBundle:Settings')->createQueryBuilder('s')
            ->select('s.value')
            ->where('s.name = :name')
            ->setParameter(':name', 'security_policy_' . strtolower($request->getLocale()))
            ->getQuery()->getSingleScalarResult();

        $work->setViews($work->getViews() + 1);
        $em->persist($work);
        $em->flush();

        if ($user = $this->getUser()) {
            $observe = $em->getRepository('AppBundle:AuctionWorkObserve')->findOneBy(array(
                'auctionWork' => $work,
                'user' => $user
            ));
        }

        return array(
            'form' => $form->createView(),
            'auction' => $auction,
            'work' => $work->getWork(),
            'workInfo' => $work,
            'nextMinPrice' => $nextMinPrice,
            'latestBid' => $work->getBids()->last(),
            'ads' => $ads,
            'security_policy' => $policy,
            'przebieg' => $przebieg,
            'observe' => isset($observe) ? $observe : null
        );
    }

    public function addNotice($type, $resipient, $message, $object)
    {

        $em = $this->getDoctrine()->getManager();
        // zwraca użytkownika imię i nazwisko
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        // zwraca id użytkownika
        $author = $user->getId();
        // zwraca nazwę użytkownika
//        $work->getWork()->getAuthor()


        $notice = new Notice();
        $notice->setRecipient($resipient)
            ->setTitle($message->getSubject())
            ->setContent($message->getBody())
            ->setObjectClass(get_class($object))
            ->setObjectId($object->getId())
            ->setIsSended(true)
            ->setIsReaded(false)
            ->setAuthor($this->getUser());
        $em->persist($notice);
        $em->flush();

    }

    public function manageNotice($type, User $resipient, Swift_Message $message, $object)
    {

        $em = $this->getDoctrine()->getManager();
        echo "test";
        $author = 0;
        if (null === $token = $this->container->get('security.token_storage')->getToken()) {
        } elseif (!is_object($author = $token->getUser())) {
            $author = 0;
        }
        $notice = new Notice();
        $notice->setRecipient($resipient)
            ->setTitle($message->getSubject())
            ->setContent($message->getBody())
            ->setObjectClass(get_class($object))
            ->setObjectId($object->getId())
            ->setIsSended(true)
            ->setIsReaded(false)
            ->setAuthor($author);
        $this->em->persist($notice);
        $this->em->flush();

    }

    public function currentUserId()
    {
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        $username = $user->getId();
        return $username;
    }

    /**
     * @Route("/buynow/{workId}", name="auction_work_buy_now", requirements={
     *  "workId": "\d+"
     * })
     */
    public function afterAuctionBuyAction($workId, Request $request)
    {
        if (!($user = $this->getUser())) {
            $this->addFlash('error', $this->get('translator')->trans('auctions.poaukcyjna.zaloguj'));
            $lastRoute = $this->get('session')->get('last_route');
            if (!empty($lastRoute) && !empty($lastRoute['name'])) {
                return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
            } else {
                return new RedirectResponse(($this->generateUrl('homepage')));
            }
        }

        $em = $this->getDoctrine()->getManager();

        $auctionWork = $em->getRepository('AppBundle:AuctionWork')->find($workId);
        if (!$auctionWork)
            throw $this->createNotFoundException();

        $auction = $auctionWork->getAuction();
        $time = new \DateTime('now');
        $lastRoute = $this->get('session')->get('last_route');

        $redirect = false;

        if ($auction->getEndDate() >= $time) {
            $this->addFlash('error', $this->get('translator')->trans('auctions.poaukcyjna.za_wczesnie'));
            $redirect = true;
        }

        if (!$auctionWork->getAllowBuyNow()) {
            $this->addFlash('error', $this->get('translator')->trans('auctions.poaukcyjna.nie_dostepny'));
            $redirect = true;
        }

        if (!$auctionWork->getBids()->isEmpty()) {
            $this->addFlash('error', $this->get('translator')->trans('auctions.poaukcyjna.zlozone_oferty'));
            $redirect = true;
        }

        if ($redirect === true) {
            if (!empty($lastRoute) && !empty($lastRoute['name'])) {
                return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
            } else {
                return new RedirectResponse(($this->generateUrl('homepage')));
            }
        }


        $currency = $em->getRepository('AppBundle:Currency')->findOneBy(array(
            'code' => $request->getSession()->get('_currency')
        ));

        try {
            $bid = new AuctionBid();
            $bid->setAuthor($this->getUser());
            $bid->setAuctionWork($auctionWork);
            $bid->setCurrency($currency);
            $bid->setAmount(
                $this->get('app.currency')->convert(
                    $auctionWork->getWork()->getCurrency()->getCode(),
                    $request->getSession()->get('_currency'),
                    $auctionWork->getBuyNowPrice()
                )
            );
            $em->persist($bid);
            $em->flush();

            $message = \Swift_Message::newInstance()
                ->setSubject($this->get('translator')->trans('auctions.poaukcyjna.mail_temat'))
                ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
                ->setTo($this->getUser()->getEmail())
                ->setBody($this->renderView('@App/Emails/auction_afterbuy_buyer.html.twig', array(
                    'work' => $auctionWork->getWork(),
                    'auction' => $auction,
                    'bid' => $bid
                )), 'text/html');
            $this->get('mailer')->send($message);

            $message = \Swift_Message::newInstance()
                ->setSubject($this->get('translator')->trans('auctions.poaukcyjna.mail_temat_seller'))
                ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
                ->setTo($this->getParameter('contact_address_from'))
                ->setBody($this->renderView('@App/Emails/auction_afterbuy_seller.html.twig', array(
                    'work' => $auctionWork->getWork(),
                    'auction' => $auction,
                    'bid' => $bid
                )), 'text/html');
            $this->get('mailer')->send($message);

            $this->addNotice('licytacja_podbita_temat', $auctionWork->getWork()->getAuthor(), $message, $auction);

            $this->addFlash('success', $this->get('translator')->trans('auctions.poaukcyjna.kupione'));
            return new RedirectResponse($this->generateUrl('user_auctions_bids'));
        } catch (\Exception $e) {
            $this->addFlash('error', $this->get('translator')->trans('auctions.poaukcyjna.nie_udalo_sie'));
        }

        if (!empty($lastRoute) && !empty($lastRoute['name'])) {
            return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
        } else {
            return new RedirectResponse(($this->generateUrl('homepage')));
        }
    }

    /**
     * @Route("/workobserve/{workId}", name="auction_work_observe", requirements={
     *  "workId": "\d+"
     * })
     */
    public
    function auctionWorkObserveAction($workId)
    {
        $lastRoute = $this->get('session')->get('last_route');

        if (!($user = $this->getUser())) {
            $this->addFlash('error', $this->get('translator')->trans('obserwuj.musisz_byc_zalogowany'));

            if (!empty($lastRoute) && !empty($lastRoute['name'])) {
                return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
            } else {
                return new RedirectResponse(($this->generateUrl('homepage')));
            }
        }

        $em = $this->getDoctrine()->getManager();
        $auctionWork = $em->getRepository('AppBundle:AuctionWork')->find($workId);
        if (!$auctionWork)
            throw $this->createNotFoundException();

        $observe = $em->getRepository('AppBundle:AuctionWorkObserve')->findBy(array(
            'auctionWork' => $auctionWork,
            'user' => $user
        ));
        if ($observe) {
            $this->addFlash('error', $this->get('translator')->trans('obserwuj.juz_obserwujesz'));
            if (!empty($lastRoute) && !empty($lastRoute['name'])) {
                return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
            } else {
                return new RedirectResponse(($this->generateUrl('homepage')));
            }
        }

        $observe = new AuctionWorkObserve();
        $observe->setAuctionWork($auctionWork);
        $observe->setUser($user);

        $em->persist($observe);
        $em->flush();

        $this->addFlash('success', $this->get('translator')->trans('obserwuj.dodano'));
        if (!empty($lastRoute) && !empty($lastRoute['name'])) {
            return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
        } else {
            return new RedirectResponse(($this->generateUrl('homepage')));
        }
    }

    /**
     * @Route("/contact/{id}", name="auction_work_ask")
     * @Template()
     */
    public
    function askAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $auctionWork = $em->getRepository('AppBundle:AuctionWork')->find($id);
        if (!$auctionWork)
            throw $this->createNotFoundException();

        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('auction_work_ask', array('id' => $id)))
            ->add('fullname', TextType::class, array(
                'label' => 'user.imie_i_nazwisko'
            ))->add('email', EmailType::class, array(
                'label' => 'kontakt.adres_email',
                'constraints' => array(new NotBlank())
            ))->add('phone', TextType::class, array(
                'label' => 'user.numer_telefonu',
                'required' => false
            ))->add('content', TextareaType::class, array(
                'label' => 'kontakt.wiadomosc',
                'constraints' => array(new NotBlank())
            ))->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $message = \Swift_Message::newInstance()
                    ->setSubject('Zapytanie o obiekt ' . $auctionWork->getWork()->getTitle())
                    ->setFrom($form->get('email')->getData())
                    ->setTo($this->getParameter('contact_address_from'))
                    ->setBody(
                        $this->renderView('AppBundle:Emails:auctionWorkAsk.html.twig', array(
                            'email' => $form->get('email')->getData(),
                            'phone' => $form->get('phone')->getData(),
                            'fullname' => $form->get('fullname')->getData(),
                            'content' => $form->get('content')->getData(),
                            'work' => $auctionWork->getWork(),
                            'auction' => $auctionWork->getAuction()
                        )),
                        'text/html'
                    );
                $this->get('mailer')->send($message);
                $this->addFlash('success', $this->get('translator')->trans('mail.wyslany'));
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('mail.nie_udalo_sie'));
            }

            $lastRoute = $this->get('session')->get('last_route');
            if (!empty($lastRoute) && !empty($lastRoute['name'])) {
                return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
            } else {
                return new RedirectResponse(($this->generateUrl('homepage')));
            }
        }

        return array(
            'form' => $form->createView()
        );
    }
}
