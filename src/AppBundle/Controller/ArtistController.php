<?php

namespace AppBundle\Controller;

use AppBundle\Entity\UserLooking;
use AppBundle\Form\LookingType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Intl;

class ArtistController extends Controller
{
   /**
     * @Route("/artists", name="artists")
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
            ->setParameter(':role', '%ROLE_ARTYSTA%')
            ->orderBy('u.id', 'DESC');
        $popularQuery = clone $query;

        //Popularni artyści
        $popular = $popularQuery->setMaxResults(12)
            ->andWhere('u.pinned = :pinned')
            ->setParameter(':pinned', true)
            ->getQuery()
            ->getResult();

        //Newsy
        $news = $em->getRepository('AppBundle:News')->createQueryBuilder('n')
            ->join('n.langs', 'nl')->addSelect('nl.title')
            ->join('nl.lang', 'l')
            ->join('n.users', 'u')->addSelect('u')
            ->where('l.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale())
            ->setMaxResults(12)
            ->orderBy('n.addDate', 'DESC')
            ->groupBy('n.id')
            ->getQuery()->getResult();

        //Ranking
        $datetime = new \DateTime('now');
        $ranking = $em->getRepository('UserBundle:UserVisits')->createQueryBuilder('uv')
            ->select('uv, u')
            ->join('uv.user', 'u')
            ->where('uv.month = :month')
            ->andWhere('uv.year = :year')
            ->setParameter(':month', $datetime->format('m'))
            ->setParameter(':year', $datetime->format('Y'))
            ->orderBy('uv.num', 'DESC')
            ->setMaxResults(10)
            ->getQuery()->getResult();

        $filters = $request->get('_filter');
        if(isset($filters['fullname']) && !empty($filters['fullname']))
        {
            $query->andWhere($qb->expr()->like('u.fullname', ':fullname'));
            $query->setParameter(':fullname', '%'.$filters['fullname'].'%');
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
            12
        );

        $adsQb = $em->getRepository('AppBundle:Advert')->createQueryBuilder('a');
        $ads = $adsQb->select('l.content')
            ->join('a.langs', 'l')
            ->join('l.lang', 'la')
            ->where($adsQb->expr()->in('a.id', array(4)))
            ->andWhere('la.shortcut = :lang')
            ->setParameter(':lang', $request->getSession()->get('_locale'))
            ->getQuery()->getResult();

//        $countries = Intl::getRegionBundle()->getCountryNames();
//        $countries = array_combine($countries, $countries);
        $countries = $this->countriesAction();

        return array(
            'popular' => $popular,
            'newses' => $news,
            'ranking' => $ranking,
            'pagination' => $pagination,
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
            ->setParameter(':role', '%ROLE_ARTYSTA%')
            ->orderBy('u.country', 'ASC');
        $query = $query->getQuery()->getResult();
        array_unique($query, SORT_REGULAR);
        
        return $query;
    }
    
    /**
     * @Route("/art/ser", name="artists_search")
     */
    public function testAction(Request $request) 
    {                
        $response = new JsonResponse();
        $q = $request->get('term');

        $em = $this->getDoctrine()->getManager();

        $qb = $em->getRepository('UserBundle:User')->createQueryBuilder('u');
        $data = $qb->select('u')
            ->where($qb->expr()->like('u.fullname', ':q'))
            ->setParameter(':q', '%'.$q.'%')
            ->distinct()
            ->getQuery()->getResult();

        if(!$data)
            return false;

        $return = array();
        foreach($data as $value)
        {
            $return[] = array(
                'id' => $value->getId(),
                'label' => $value->getFullname() ? $value->getFullname() : $value->getUsername(),
                'username' => $value->getUsername(),
                'image' => $value->getImage(),
                'slug' => $this->get('slugify')->slugify($value->profileSlug),
                'prefix' => $value->profilePrefix,
                'country' => $value->getCountry() ? $value->getCountry() : '?',
                'birthdate' => $value->getBirthdate() ? $value->getBirthdate()->format('Y-m-d'): '?'
            );
        }
        
        $response->setData($return);
        return $response;
    }
    
    /**
     * @Route("/artists/search", name="artists_search")
     */
//    public function searchAction(Request $request)
//    {
//        
//        $response = new JsonResponse();
//        $q = $request->get('term');
//
//        $em = $this->getDoctrine()->getManager();
//
//        $qb = $em->getRepository('UserBundle:User')->createQueryBuilder('u');
//        $data = $qb->select('u')
//            ->where($qb->expr()->like('u.fullname', ':q'))
//            ->setParameter(':q', '%'.$q.'%')
//            ->distinct()
//            ->getQuery()->getResult();
//
//        if(!$data)
//            return false;
//
//        $return = array();
//        foreach($data as $value)
//        {
//            $return[] = array(
//                'id' => $value->getId(),
//                'label' => $value->getFullname() ? $value->getFullname() : $value->getUsername(),
//                'username' => $value->getUsername(),
//                'image' => $value->getImage(),
//                'slug' => $this->get('slugify')->slugify($value->profileSlug),
//                'prefix' => $value->profilePrefix,
//                'country' => $value->getCountry() ? $value->getCountry() : '?',
//                'birthdate' => $value->getBirthdate() ? $value->getBirthdate()->format('Y-m-d'): '?'
//            );
//        }
//        
//        $response->setData($return);
//        return $response;
//    }
    

    /**
     * @Route("/sers/te", name="artists_top_month")
     */
    public function topMonthAction(Request $request)
    {
        $month = $request->query->getInt('month', 0);
        
        $datetime = new \DateTime('-'.$month.' month');
        $em = $this->getDoctrine()->getManager();
        
        $qb = $em->getRepository('UserBundle:UserVisits')->createQueryBuilder('uv');
        $ranking = $qb->select('uv, u')
            ->join('uv.user', 'u')
            ->where('uv.month = :month')
            ->andWhere('uv.year = :year')
            ->andWhere($qb->expr()->like('u.roles', ':role'))
            ->setParameter(':month', $datetime->format('m'))
            ->setParameter(':year', $datetime->format('Y'))
            ->setParameter(':role', '%ROLE_ARTYSTA%')
            ->orderBy('uv.num', 'DESC')
            ->setMaxResults(10)
            ->getQuery()->getResult();
        
        return $this->render('AppBundle:Artist:topMonth.html.twig', array(
            'ranking' => $ranking
        ));
    }
    
    
//    /**
//     * @Route("/artists/top", name="artists_top_month")
//     */
//    public function topMonthAction(Request $request)
//    {
//        echo "test";
//        $month = $request->query->getInt('month', 0);
//        
//        $datetime = new \DateTime('-'.$month.' month');
//        $em = $this->getDoctrine()->getManager();
//        
//        $qb = $em->getRepository('UserBundle:UserVisits')->createQueryBuilder('uv');
//        $ranking = $qb->select('uv, u')
//            ->join('uv.user', 'u')
//            ->where('uv.month = :month')
//            ->andWhere('uv.year = :year')
//            ->andWhere($qb->expr()->like('u.roles', ':role'))
//            ->setParameter(':month', $datetime->format('m'))
//            ->setParameter(':year', $datetime->format('Y'))
//            ->setParameter(':role', '%ROLE_ARTYSTA%')
//            ->orderBy('uv.num', 'DESC')
//            ->setMaxResults(10)
//            ->getQuery()->getResult();
//        
//        return $this->render('AppBundle:Artist:topMonth.html.twig', array(
//            'ranking' => $ranking
//        ));
//    }

    /**
     * @Route("look/artists/looking", name="artists_looking")
     * @Template()
     */
    public function lookingAction(Request $request)
    {
        if(!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_DOM_AUKCYJNY') && !$this->isGranted('ROLE_GALERIA'))
            throw $this->createAccessDeniedException();

        $em = $this->getDoctrine()->getManager();

        $look = new UserLooking();
        $look->setUser($this->getUser());

        $form = $this->createForm(LookingType::class, $look);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $em->persist($look);
            $em->flush();
            $this->addFlash('success', $this->get('translator')->trans('main.dane_zmienione'));
        }

        $looking = $em->getRepository('AppBundle:UserLooking')->createQueryBuilder('ul')
            ->join('ul.target', 't')->addSelect('t')
            ->where('ul.user = :user')
            ->setParameter(':user', $this->getUser())
            ->getQuery()->getResult();

        return array(
            'form' => $form->createView(),
            'looking' => $looking
        );
    }

    /**
     * @Route("look/artists/looking/delete/{id}", name="artists_looking_delete", requirements={
     *  "id": "\d+"
     * })
     */
    public function lookingDeleteAction($id)
    {


//        $look = $em->getRepository('AppBundle:UserLooking')->createQueryBuilder('ul')
//            ->where('ul.user = :user')
//            ->andWhere('ul.id = :id')
//            ->setParameter(':id', $id)
//            ->setParameter(':user', $this->getUser())
//            ->getQuery()->getSingleResult();   
        try{
            $sql = " 
            DELETE FROM user_looking          
              WHERE id = :id
            ";
            $params['id'] = $id;

            $em = $this->getDoctrine()->getManager();
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->execute($params);
//            $stmt->fetchAll();       

            $this->addFlash('success', $this->get('translator')->trans('artists.poszukiwany_usuniety'));
            return $this->redirectToRoute('artists_looking');
        } catch (Exception $ex) {
            $this->addFlash('success', $this->get('translator')->trans('artists.poszukiwany_usuniety'));
            return $this->redirectToRoute('artists_looking');
        }
        
         return $this->redirectToRoute('artists_looking');
 
    }
    
}
