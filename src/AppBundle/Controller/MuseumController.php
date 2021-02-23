<?php
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class MuseumController extends Controller
{
    /**
     * @Route("/museum", name="museum")
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

        $qb = $em->getRepository('UserBundle:User')->createQueryBuilder('u');

        $query = $qb->select('u')
            ->where($qb->expr()->like('u.roles', ':role'))
            ->andWhere('u.enabled = :enabled')
            ->setParameter(':enabled', true)
            ->setParameter(':role', '%ROLE_MUZEUM%')
            ->orderBy('u.id', 'DESC');
        $popularQuery = clone $query;

        //Popularne galerie
        $popular = $popularQuery->setMaxResults(12)
            ->andWhere('u.pinned = :pinned')
            ->setParameter(':pinned', true)
            ->getQuery()
            ->getResult();

        $qb = $em->getRepository('AppBundle:Exhibition')->createQueryBuilder('n');
        $ongoing_exhibitions = $qb
            ->select('n, u')
            ->join('n.author', 'u')
            ->join('n.langs', 'nl')->addSelect('nl.title, nl.description')
            ->join('nl.lang', 'l')
            ->where('n.approved = :approved')
            ->andWhere('n.startDate < :date')
            ->andWhere('l.shortcut = :shortcut')
            ->andWhere($qb->expr()->like('u.roles', ':role'))
            ->setParameter(':role', '%ROLE_MUZEUM%')
            ->setParameter(':shortcut', $request->getLocale())
            ->setParameter(':approved', true)
            ->setParameter(':date', new \DateTime('now'))
            ->orderBy('n.endDate')
            ->setMaxResults(12)
            ->getQuery()->getResult();

        $qb = $em->getRepository('AppBundle:Exhibition')->createQueryBuilder('n');
        $exhibitions = $qb
            ->join('n.author', 'u')->addSelect('u')
            ->join('n.langs', 'nl')->addSelect('nl.title, nl.description')
            ->join('nl.lang', 'l')
            ->where('n.approved = :approved')
            ->andWhere('n.startDate > :date')
            ->andWhere('l.shortcut = :shortcut')
            ->andWhere($qb->expr()->like('u.roles', ':role'))
            ->setParameter(':role', '%ROLE_MUZEUM%')
            ->setParameter(':shortcut', $request->getLocale())
            ->setParameter(':approved', true)
            ->setParameter(':date', new \DateTime('now'))
            ->orderBy('n.endDate')
            ->setMaxResults(12)
            ->getQuery()->getResult();

        $usersQb = $em->getRepository('UserBundle:User')->createQueryBuilder('u');
        $countries = $usersQb
            ->distinct()
            ->select('u.country')
            ->where('u.enabled = :enabled')
            ->andWhere('u.country != :empty')
            ->andWhere($usersQb->expr()->like('u.roles', ':role'))
            ->setParameter(':enabled', true)
            ->setParameter(':role', '%ROLE_MUZEUM%')
            ->setParameter(':empty', '')
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
            ->where($adsQb->expr()->in('a.id', array(5)))
            ->andWhere('la.shortcut = :lang')
            ->setParameter(':lang', $request->getSession()->get('_locale'))
            ->getQuery()->getResult();

        return array(
            'popular' => $popular,
            'pagination' => $pagination,
            'ongoing_exhibitions' => $ongoing_exhibitions,
            'exhibitions' => $exhibitions,
            'countries' => $countries,
            'ads' => $ads
        );
    }

}
