<?php

namespace AppBundle\Controller;

use Doctrine\ORM\NoResultException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("news")
 */
class NewsController extends Controller
{
    /**
     * @Route("/", name="newses")
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

        $qb = $em->getRepository('AppBundle:News')->createQueryBuilder('n');
        $query = $qb->select('n')
            ->join('n.category', 'c')->addSelect('c')
            ->join('n.langs', 'nl')->addSelect('nl')
            ->join('nl.lang', 'l')
            ->where('l.shortcut = :lang')
            ->setParameter(':lang', $request->getLocale())
            ->orderBy('n.id', 'DESC');
        $popularQuery = clone $query;
        $latestQuery = clone $query;

        $latest = $latestQuery->orderBy('n.id', 'DESC')
            ->setMaxResults(6)
            ->getQuery()->getResult();

        $popular = $popularQuery->andWhere('n.pinned = :pinned')
            ->setParameter(':pinned', true)
            ->setMaxResults(6)
            ->getQuery()->getResult();

        $categories = $em->getRepository('AppBundle:NewsCategory')->createQueryBuilder('nc')
            ->select('nc')
            ->join('nc.langs', 'cl')->addSelect('cl.title')
            ->join('cl.lang', 'l')
            ->where('l.shortcut = :lang')
            ->setParameter(':lang', $request->getLocale())
            ->getQuery()->getResult();

        $filters = $request->get('_filter');
        if(isset($filters['title']) && !empty($filters['title']))
        {
            $query->andWhere($qb->expr()->like('nl.title', ':title'));
            $query->setParameter(':title', '%'.$filters['title'].'%');
        }
        if(isset($filters['category']) && !empty($filters['category']))
        {
            $query->andWhere('c.id = :category')
                ->setParameter(':category', $filters['category']);
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
            ->where($adsQb->expr()->in('a.id', array(9)))
            ->andWhere('la.shortcut = :lang')
            ->setParameter(':lang', $request->getSession()->get('_locale'))
            ->getQuery()->getResult();

        return array(
            'pagination' => $pagination,
            'popular' => $popular,
            'latest' => $latest,
            'categories' => $categories,
            'ads' => $ads
        );
    }
    
    /**
     * @Route("/{slug}", name="news", requirements={
     *  "slug": "[a-zA-Z0-9\-_]+",
     * })
     * @Template()
     */
    public function seeAction($slug, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $qb = $em->getRepository('AppBundle:News')->createQueryBuilder('n');

        try {
            $news = $qb->select('n')
                ->join('n.langs', 'nl')->addSelect('nl')
                ->join('n.category', 'c')->addSelect('c')
                ->join('n.author', 'u')->addSelect('u')
                ->join('nl.lang', 'l')
                ->where('l.shortcut = :lang')
                ->andWhere('n.slug = :slug')
                ->setParameter(':slug', $slug)
                ->setParameter(':lang', $request->getLocale())
                ->getQuery()->getSingleResult();
        } catch(NoResultException $e)
        {
            return $this->render('AppBundle:News:noLang.html.twig');
        }

        if(!$news)
            return $this->render('AppBundle:News:noLang.html.twig');

        $category = $em->getRepository('AppBundle:NewsCategory')->createQueryBuilder('nc')
            ->join('nc.langs', 'nl')->addSelect('nl.title')
            ->join('nl.lang', 'l')
            ->where('nc.id = :id')
            ->andWhere('l.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale())
            ->setParameter(':id', $news->getCategory()->getId())
            ->getQuery()->getSingleResult();

        $news->setViews($news->getViews() + 1);
        $em->persist($news);
        $em->flush();
        
        return array(
            'news' => $news,
            'category' => $category
        );
    }
}
