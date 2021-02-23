<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpFoundation\RedirectResponse;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $articles = $em->getRepository('AppBundle:News')->createQueryBuilder('n')
            ->select('n')
            ->addSelect('nl')
            ->join('n.langs', 'nl')
            ->join('nl.lang', 'l')
            ->where('l.shortcut = :shortcut')
            ->setMaxResults(4)
            ->orderBy('n.id', 'DESC')
            ->setParameter(':shortcut', $request->getLocale())
            ->getQuery()->getResult();

        $auctions = $em->getRepository('AppBundle:Auction')->createQueryBuilder('a')
            ->select('a')
            ->join('a.langs', 'l')->addSelect('l.title, l.description')
            ->join('l.lang', 'lg')
            ->where('a.startDate > :dateStart')
            ->andWhere('a.endDate > :dateEnd')
            ->andWhere('lg.shortcut = :shortcut')
            ->setParameter(':dateStart', new \DateTime('now'))
            ->setParameter(':dateEnd', new \DateTime('now'))
            ->setParameter(':shortcut', $request->getLocale())
            ->setMaxResults(6)
            ->orderBy('a.id', 'DESC')
            ->getQuery()->getResult();

//        $works = $em->getRepository('AppBundle:Work')->createQueryBuilder('w')
//            ->select('w')
//            ->join('w.auctionWorks', 'aw')->addSelect('aw')
//            ->join('UserBundle:User', 'au', 'WITH', 'au.fullname = w.artist and au.roles LIKE :role_a')->addSelect('au.id, au.fullname')
//            ->where('w.approved = :approved')
//            ->where('w.display = :approved')
//            ->andWhere('w.pinned = :pinned')
//            ->setMaxResults(6)
//            ->orderBy('w.id', 'DESC')
//            ->setParameter(':approved', true)
//            ->setParameter(':pinned', true)
//            ->setParameter(':role_a', '%ROLE_ARTYSTA%')
//            ->getQuery()->getResult();
        $works = $em->getRepository('AppBundle:Work')->createQueryBuilder('w') 
                ->select('w')
                ->leftjoin('w.auctionWorks', 'aw')->addSelect('aw')
                ->andWhere('w.approved =:approved')
                ->andWhere('w.display = :approved')
                ->andWhere('w.pinned = :pinned')
                ->setMaxResults(6)
                ->orderBy('w.id', 'DESC')
                ->setParameter(':approved', true)
                ->setParameter(':pinned', true)
                ->getQuery()->getResult();
        
        $pinnedEvents = $em->getRepository('AppBundle:Event')->createQueryBuilder('e')
            ->join('e.langs', 'el')->addSelect('el.title, el.description')
            ->join('el.lang', 'l')
            ->where('l.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale())
                ->andWhere('e.pinned = :pinned')
                ->setParameter('pinned', true)
            ->orderBy('e.id', 'DESC')
                ->getQuery()->getResult();

        $userQb = $em->getRepository('UserBundle:User')->createQueryBuilder('u');

        $artists = $userQb->select('u')
            ->where('u.pinned = :pinned')
            ->andWhere($userQb->expr()->like('u.roles', ':role'))
            ->setParameter('pinned', true)
            ->setParameter('role', '%ROLE_ARTYSTA%')
            ->orderBy('u.id', 'DESC')
            ->setMaxResults(8)->getQuery()->getResult();
        foreach( $artists as &$user ){
            $user->setProfilePrefix( 'artist');
        }
        $houses = $userQb->select('u')
            ->where('u.pinned = :pinned')
            ->andWhere($userQb->expr()->like('u.roles', ':role'))
            ->setParameter('pinned', true)
            ->setParameter('role', '%ROLE_DOM_AUKCYJNY%')
            ->orderBy('u.id', 'DESC')
            ->setMaxResults(8)->getQuery()->getResult();
        foreach( $houses as &$user ){
            $user->setProfilePrefix( 'auction-house');
        }

        $galleries = $userQb->select('u')
            ->where('u.pinned = :pinned')
            ->andWhere($userQb->expr()->like('u.roles', ':role'))
            ->setParameter('pinned', true)
            ->setParameter('role', '%ROLE_GALERIA%')
            ->orderBy('u.id', 'DESC')
            ->setMaxResults(6)->getQuery()->getResult();

        foreach( $galleries as &$user ){
            $user->setProfilePrefix( 'gallery');
        }



        $museums = $userQb->select('u')
            ->where('u.pinned = :pinned')
            ->andWhere($userQb->expr()->like('u.roles', ':role'))
            ->setParameter('pinned', true)
            ->setParameter('role', '%ROLE_MUZEUM%')
            ->orderBy('u.id', 'DESC')
            ->setMaxResults(6)->getQuery()->getResult();
        foreach( $museums as &$user ){
            $user->setProfilePrefix( 'museum');
        }
        $adsQb = $em->getRepository('AppBundle:Advert')->createQueryBuilder('a');
        $ads = $adsQb->select('l.content')
            ->join('a.langs', 'l')
            ->join('l.lang', 'la')
            ->where($adsQb->expr()->in('a.id', array(1,2)))
            ->andWhere('la.shortcut = :lang')
            ->setParameter(':lang', $request->getSession()->get('_locale'))
            ->getQuery()->getResult();

//        $banner = $em->getRepository('AppBundle:Settings')->createQueryBuilder('s')
//            ->where('s.name = :name')
//            ->setParameter(':name', 'homepage_banner')
//            ->getQuery()->getSingleResult();
//        $bannerValue = $banner->getValue();
//        switch($bannerValue)
//        {
//            case '%prezentacja_galerii%':
//                $userQb = $em->getRepository('UserBundle:User')->createQueryBuilder('u');
//                $banner = $userQb->select('u')
//                    ->select('u')
//                    ->where($userQb->expr()->like('u.roles', ':role'))
//                    ->setParameter(':role', '%ROLE_GALERIA%')
//                    ->setMaxResults(6)->getQuery()->getResult();
//                break;
//            case '%nadchodzace_aukcje%':
//                $banner = $em->getRepository('AppBundle:Auction')->createQueryBuilder('a')
//                    ->where('a.startDate > :date')
//                    ->orderBy('a.startDate', 'DESC')
//                    ->setParameter(':date', new \DateTime('now'))
//                    ->setMaxResults(6)->getQuery()->getResult();
//                break;
//            case '%wyniki_aukcji%':
//                $banner = $em->getRepository('AppBundle:AuctionWork')->createQueryBuilder('aw')
//                    ->join('aw.auction', 'a')
//                    ->join('aw.bids', 'b')->addSelect('MAX(b.amount)')
//                    ->join('aw.work', 'w')->addSelect('w')
//                    ->where('a.endDate < :date')
//                    ->setParameter(':date', new \DateTime('now'))
//                    ->setMaxResults(6)->getQuery()->getResult();
//                break;
//            case '%nadchodzace_wystawy%':
//                $banner = $em->getRepository('AppBundle:Exhibition')->createQueryBuilder('e')
//                    ->where('e.startDate > :date')
//                    ->andWhere('e.approved = :approved')
//                    ->orderBy('e.startDate', 'DESC')
//                    ->setParameter(':date', new \DateTime('now'))
//                    ->setParameter(':approved', true)
//                    ->setMaxResults(6)->getQuery()->getResult();
//                break;
//            case '%polecane_wydarzenia%':
//                $banner = $em->getRepository('AppBundle:Event')->createQueryBuilder('e')
//                    ->where('e.pinned = :pinned')
//                    ->setParameter(':pinned', true)
//                    ->setMaxResults(6)->getQuery()->getResult();
//                break;
//            case '%najnowsze_aktualnosci%':
//                $banner = $em->getRepository('AppBundle:News')->createQueryBuilder('n')
//                    ->join('n.langs', 'nl')->addSelect('nl')
//                    ->join('nl.lang', 'l')
//                    ->where('l.shortcut = :lang')
//                    ->setParameter(':lang', $request->getLocale())
//                    ->orderBy('n.id', 'DESC')
//                    ->setMaxResults(6)->getQuery()->getResult();
//                break;
//        }

        $bannerSpeed = $em->getRepository('AppBundle:Settings')->createQueryBuilder('s')
            ->where('s.name = :name')
            ->setParameter(':name', 'homepage_banner_speed')
            ->getQuery()->getSingleResult();
        $speed = strip_tags($bannerSpeed->getValue());

 $bidedOferts = $em->getRepository('AppBundle:AuctionWork')->createQueryBuilder('aw')
            ->leftJoin('aw.bids', 'b')->addSelect('COUNT(b.id) AS bids')
            ->join('aw.work', 'w')->addSelect('w')
            ->join('aw.auction', 'a')->addSelect('a')
            ->join('a.langs', 'l')->addSelect('l.title, l.description')
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
            ->join('a.langs', 'l')->addSelect('l.title, l.description')
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
            ->join('a.langs', 'l')->addSelect('l.title, l.description')
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

        return array(
            'bidedOferts' => $bidedOferts,
            'bidedViews' => $bidedViews,
            'bidedObserved' => $bidedObserved,
            'articles' => $articles,
            'works' => $works,
            'auctions' => $auctions,
            'events' => $pinnedEvents,
            'artists' => $artists,
            'houses' => $houses,
            'museums' => $museums,
            'galleries' => $galleries,
            'ads' => $ads,
            'banners' => $em->getRepository('AdminBundle:Banner')->createQueryBuilder('b')
                ->join('b.langs', 'l')->addSelect('l')
                ->join('l.lang', 'lg')
                ->where('lg.shortcut = :shortcut')
                ->andWhere('b.active = :active')
                ->setParameter(':active', true)
                ->setParameter(':shortcut', $request->getLocale())
                ->orderBy('b.position')
                ->getQuery()->getResult(),
            'bannerSpeed' => $speed

//            'banner' => $banner,
//            'bannerValue' => $bannerValue
        );
    }

    /**
     * @Route("/search", name="main_search")
     * @Template()
     */
    public function searchAction(Request $request)
    {
        $q = $request->get('term');
        $em = $this->getDoctrine()->getManager();

        $usersQb = $em->getRepository('UserBundle:User')->createQueryBuilder('u');
        $users = $usersQb->where($usersQb->expr()->like('u.fullname', ':fullname'))
            ->andWhere(
                $usersQb->expr()->like('u.roles', ':role_a') . ' OR ' .
                $usersQb->expr()->like('u.roles', ':role_m') . ' OR ' .
                $usersQb->expr()->like('u.roles', ':role_g') . ' OR ' .
                $usersQb->expr()->like('u.roles', ':role_d')
            )
            ->setParameter(':fullname', '%'.$q.'%')
            ->setParameter(':role_a', '%'.'ROLE_ARTYSTA'.'%')
            ->setParameter(':role_m', '%'.'ROLE_MUZEUM'.'%')
            ->setParameter(':role_g', '%'.'ROLE_GALERIA'.'%')
            ->setParameter(':role_d', '%'.'ROLE_DOM_AUKCYJNY'.'%')
            ->setMaxResults(5)
            ->getQuery()->getResult();

        $newsQb = $em->getRepository('AppBundle:News')->createQueryBuilder('n');
        $newses = $newsQb->join('n.langs', 'l')->addSelect('l.title')
            ->join('l.lang', 'lg')
            ->join('n.category', 'c')
            ->join('c.langs', 'cl')->addSelect('cl.title AS catTitle')
            ->join('cl.lang', 'clg')
            ->where($newsQb->expr()->like('l.title', ':title'))
            ->andWhere('lg.shortcut = :shortcut')
            ->andWhere('clg.shortcut = :shortcut')
            ->setParameter(':title', '%'.$q.'%')
            ->setParameter(':shortcut', $request->getLocale())
            ->setMaxResults(5)
            ->getQuery()->getResult();

        $eventsQb = $em->getRepository('AppBundle:Event')->createQueryBuilder('e');
        $events = $eventsQb->join('e.langs', 'l')->addSelect('l.title')
            ->join('l.lang', 'lg')
            ->join('e.category', 'c')
            ->join('c.langs', 'cl')->addSelect('cl.title AS catTitle')
            ->join('cl.lang', 'clg')
            ->where($newsQb->expr()->like('l.title', ':title'))
            ->andWhere('lg.shortcut = :shortcut')
            ->andWhere('clg.shortcut = :shortcut')
            ->setParameter(':title', '%'.$q.'%')
            ->setParameter(':shortcut', $request->getLocale())
            ->setMaxResults(5)
            ->getQuery()->getResult();

        $auctionsQb = $em->getRepository('AppBundle:Auction')->createQueryBuilder('a');
        $auctions = $auctionsQb->join('a.langs', 'l')->addSelect('l.title')
//            ->join('l.lang', 'lg')
            ->where($newsQb->expr()->like('l.title', ':title'))
//            ->andWhere('lg.shortcut = :shortcut')
            ->setParameter(':title', '%'.$q.'%')
//            ->setParameter(':shortcut', $request->getLocale())
            ->setMaxResults(5)
//            ->orderBy('a.endDate')
            ->getQuery()->getResult();

        $worksQb = $em->getRepository('AppBundle:Work')->createQueryBuilder('w');
        $works = $worksQb->leftJoin('w.auctionWorks', 'aw')->addSelect('aw')
            ->leftJoin('aw.auction', 'a')->addSelect('a')
            ->leftJoin('aw.bids', 'b')->addSelect('b')
            ->where($worksQb->expr()->like('w.title', ':q') . ' OR ' . $worksQb->expr()->like('w.artist', ':q'))
            ->andWhere('w.approved = :true')
            ->setParameter('q', '%'.$q.'%')
            ->setParameter(':true', true)
            ->setMaxResults(5)
            ->getQuery()->getResult();

        return [
            'users' => $users,
            'newses' => $newses,
            'events' => $events,
            'auctions' => $auctions,
            'works' => $works
        ];
    }

}
