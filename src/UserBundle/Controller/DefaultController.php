<?php

namespace UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;
use UserBundle\Entity\UserVisits;

class DefaultController extends Controller
{
    /**
     * @Route("/{prefix}/{slug}", name="profile", requirements={
     *  "prefix": "(profile|artists|redactor|galleries|auction-houses|museum)",
     *  "slug": "[a-zA-Z0-9\-_]+"
     * })
     * @Template()
     */
    public function profileAction($slug)
    {
//        $user = $this->container->get('security.token_storage')->getToken()->getUser();
//        $userId = $user->getId();
//
//        $user = $userManager->findUserBy(array('id'=>$userId));

        $userManager = $this->get('fos_user.user_manager');
        $em = $this->getDoctrine()->getManager();
        $datetime = new \DateTime("now");

        $user = $userManager->findUserBy(array('profileSlug'=>$slug));
        if(!$user)
            throw $this->createNotFoundException ();
        
        if(!$this->isGranted('ROLE_ADMIN'))
        {
            if(
                    !$user->hasRole('ROLE_ARTYSTA') &&
                    !$user->hasRole('ROLE_GALERIA') &&
                    !$user->hasRole('ROLE_DOM_AUKCYJNY') &&
                    !$user->hasRole('ROLE_MUZEUM') &&
                    !$user->hasRole('ROLE_REDAKTOR')
            )
            {
//                throw $this->createNotFoundException();
            }
        }

        /**
         * UPDATE VISITS STATS
         */
        $userVisit = $em->getRepository('UserBundle:UserVisits')->createQueryBuilder('uv')
        ->select('uv')
        ->where('uv.user = :user')
        ->andWhere('uv.month = :month')
        ->andWhere('uv.year = :year')
        ->setParameter(':user', $user)
        ->setParameter(':month', $datetime->format('m'))
        ->setParameter(':year', $datetime->format('Y'))
        ->getQuery()->getResult();
        if($userVisit)
        {
            $userVisit = $userVisit[0]->setNum((int)$userVisit[0]->getNum() + 1);
        } else
        {
            $userVisit = new UserVisits();
            $userVisit->setNum(1)->setUser($user)->setMonth($datetime->format('m'))->setYear($datetime->format('Y'));
        }
        $em->persist($userVisit);
        $em->flush();
        
        if($loggedUser = $this->getUser())
        {
            $observe = $em->getRepository('AppBundle:ProfileObserve')->findBy(array(
                'targetUser' => $user,
                'user' => $loggedUser
            ));
        }
        
        return array(
            'user' => $user,
            'role' => $this->getRoles($user->getRoles()),
            'observe' => isset($observe) ? $observe : null
        );
    }
    
    /**
     * @Route("/profile/observe/{id}", name="profile_observe", requirements={
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
        $userManager = $this->get('fos_user.user_manager');

        $targetUser = $userManager->findUserBy(array('id'=>$id));
        if(!$targetUser)
            throw $this->createNotFoundException ();
        
        $observe = $em->getRepository('AppBundle:ProfileObserve')->findBy(array(
            'targetUser' => $targetUser,
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
        
        $observe = new \AppBundle\Entity\ProfileObserve();
        $observe->setTargetUser($targetUser);
        $observe->setUser($user);
        
        $em->persist($observe);
        $em->flush();
        
        $this->addFlash('success', $this->get('translator')->trans('obserwuj.dodano'));
        return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
    }

    /**
     * @Route("/{prefix}/{slug}/works", name="profile_works", requirements={
     *  "prefix": "(profile|artists|redactor|galleries|auction-houses|museum)",
     *  "slug": "[a-zA-Z0-9\-_]+"
     * })
     * @Template()
     */
    public function profileWorksAction($slug, Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');
        $em = $this->getDoctrine()->getManager();

        $user = $userManager->findUserBy(array('profileSlug'=>$slug));
        if(!$user)
            throw $this->createNotFoundException ();

        $qb = $em->getRepository('AppBundle:Work')->createQueryBuilder('w');

        $query = $qb
            ->select('w')
            ->leftJoin('w.auctionWorks', 'aw')
            ->where('w.author = :author OR w.artist = :artist')
            ->andWhere('w.approved = :approved')
            ->orderBy('w.id', 'DESC')
            ->setParameter(':approved', true)
            ->setParameter(':artist', $user->getFullname())
            ->setParameter(':author', $user);

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
        if(isset($filters['price_on_request']) && !empty($filters['price_on_request']))
        {
            $query->andWhere('w.priceOnRequest = :priceOnRequest');
            $query->setParameter(':priceOnRequest', true);
        }

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            25
        );
        
        if($loggedUser = $this->getUser())
        {
            $observe = $em->getRepository('AppBundle:ProfileObserve')->findBy(array(
                'targetUser' => $user,
                'user' => $loggedUser
            ));
        }

        return array(
            'user' => $user,
            'pagination' => $pagination,
            'role' => $this->getRoles($user->getRoles()),
            'observe' => isset($observe) ? $observe : false
        );
    }
    
    /**
     * @Route("/{prefix}/{slug}/events", name="profile_events", requirements={
     *  "prefix": "(profile|artists|redactor|galleries|auction-houses)",
     *  "slug": "[a-zA-Z0-9\-_]+"
     * })
     * @Template()
     */
    public function profileEventsAction($slug, Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');
        $em = $this->getDoctrine()->getManager();

        $user = $userManager->findUserBy(array('profileSlug'=>$slug));
        if(!$user)
            throw $this->createNotFoundException ();

//        $qb = $em->getRepository('AppBundle:Exhibition')->createQueryBuilder('n');
//
//        $query = $qb
//            ->select('n')
//            ->innerJoin('n.users', 'u', 'WITH', 'u.id = :user')
//            ->setParameter(':user', $user)
//            ->orderBy('n.id', 'DESC');
        $qb = $em->getRepository('AppBundle:Event')->createQueryBuilder('e');
        $query = $qb->join('e.users', 'u', 'WITH', 'u.id = :user')
            ->join('e.langs', 'el')->addSelect('el.title')
            ->join('el.lang', 'l')
            ->where('l.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale())
            ->setParameter(':user', $user)
            ->orderBy('e.id', 'DESC');

        $filters = $request->get('_filter');
        if(isset($filters['title']) && !empty($filters['title']))
        {
            $query->andWhere($qb->expr()->like('el.title', ':title'));
            $query->setParameter(':title', '%'.$filters['title'].'%');
        }

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );
        
        if($loggedUser = $this->getUser())
        {
            $observe = $em->getRepository('AppBundle:ProfileObserve')->findBy(array(
                'targetUser' => $user,
                'user' => $loggedUser
            ));
        }

        return array(
            'user' => $user,
            'pagination' => $pagination,
            'role' => $this->getRoles($user->getRoles()),
            'observe' => isset($observe) ? $observe : false
        );
    }
    
    /**
     * @Route("/{prefix}/{slug}/news", name="profile_news", requirements={
     *  "prefix": "(profile|artists|redactor|galleries|auction-houses|museum)",
     *  "slug": "[a-zA-Z0-9\-_]+"
     * })
     * @Template()
     */
    public function profileNewsAction($slug, Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');
        $em = $this->getDoctrine()->getManager();

        $user = $userManager->findUserBy(array('profileSlug' => $slug));
        if (!$user)
            throw $this->createNotFoundException();

        $qb = $em->getRepository('AppBundle:News')->createQueryBuilder('n');

        if ($user->hasRole('ROLE_REDAKTOR'))
        {
            $query = $qb
                ->select('n')
                ->join('n.langs', 'nl')->addSelect('nl')
                ->join('nl.lang', 'l')
                ->where('l.shortcut = :lang')
                ->andWhere('n.author = :user')
                ->setParameter(':lang', $request->getLocale())
                ->setParameter(':user', $user)
                ->orderBy('n.id', 'DESC');
        }
        else
        {
            $query = $qb
                ->select('n')
                ->innerJoin('n.users', 'u', 'WITH', 'u.id = :user')
                ->join('n.langs', 'nl')->addSelect('nl')
                ->join('nl.lang', 'l')
                ->where('l.shortcut = :lang')
                ->setParameter(':lang', $request->getLocale())
                ->setParameter(':user', $user)
                ->orderBy('n.id', 'DESC');
        }

        $filters = $request->get('_filter');
        if(isset($filters['title']) && !empty($filters['title']))
        {
            $query->andWhere($qb->expr()->like('n.title', ':title'));
            $query->setParameter(':title', '%'.$filters['title'].'%');
        }

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );
        
        if($loggedUser = $this->getUser())
        {
            $observe = $em->getRepository('AppBundle:ProfileObserve')->findBy(array(
                'targetUser' => $user,
                'user' => $loggedUser
            ));
        }

        return array(
            'user' => $user,
            'pagination' => $pagination,
            'role' => $this->getRoles($user->getRoles()),
            'observe' => isset($observe) ? $observe : false
        );
    }
    
    /**
     * @Route("/{prefix}/{slug}/artists", name="profile_artists", requirements={
     *  "prefix": "(galleries)",
     *  "slug": "[a-zA-Z0-9\-_]+"
     * })
     * @Template()
     */
    public function profileArtistsAction($slug, Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');
        $em = $this->getDoctrine()->getManager();

        $user = $userManager->findUserBy(array('profileSlug'=>$slug));
        if(!$user)
            throw $this->createNotFoundException ();

        $qb = $em->getRepository('AppBundle:Work')->createQueryBuilder('w');
        $query = $qb->join('UserBundle:User', 'au', 'WITH', 'au.fullname = w.artist and au.roles LIKE :role_a')->select('au')
            ->where('w.author = :user')
            ->setParameter(':user', $user)
            ->setParameter(':role_a', '%ROLE_ARTYSTA%');

        $filters = $request->get('_filter');
        if(isset($filters['title']) && !empty($filters['title']))
        {
            $query->andWhere($qb->expr()->like('au.fullname', ':fullname'));
            $query->setParameter(':fullname', '%'.$filters['title'].'%');
        }

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );
        
        if($loggedUser = $this->getUser())
        {
            $observe = $em->getRepository('AppBundle:ProfileObserve')->findBy(array(
                'targetUser' => $user,
                'user' => $loggedUser
            ));
        }

        return array(
            'user' => $user,
            'pagination' => $pagination,
            'role' => $this->getRoles($user->getRoles()),
            'observe' => isset($observe) ? $observe : false
        );
    }
    
    /**
     * @Route("/{prefix}/{slug}/auctions", name="profile_auctions", requirements={
     *  "prefix": "(auction-houses)",
     *  "slug": "[a-zA-Z0-9\-_]+"
     * })
     * @Template()
     */
    public function profileAuctionsAction($slug, Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');
        $em = $this->getDoctrine()->getManager();

        $user = $userManager->findUserBy(array('profileSlug'=>$slug));
        if(!$user)
            throw $this->createNotFoundException ();

        $qb = $em->getRepository('AppBundle:HouseAuction')->createQueryBuilder('n');

        $query = $qb
            ->select('n.id, n.image')
            ->where('n.author = :user')
            ->andWhere('n.approved = :enabled')
            ->setParameter(':user', $user)
            ->setParameter(':enabled', true)
            ->orderBy('n.id', 'DESC')
            ->join('n.langs', 'l')
            ->addSelect('l.title, l.description');
//            ->andWhere('l.auction = :auction')
//            ->setParameter(':auction', '');

        $filters = $request->get('_filter');
        if(isset($filters['auctions']) && is_array($filters['auctions']) && !empty($filters['auctions']))
        {
            if(in_array('next', $filters['auctions']))
            {
                $query->andWhere('n.startDate > :date');
            }
            if(in_array('ended', $filters['auctions']))
            {
                $query->andWhere('n.startDate < :date');
            }
            $query->setParameter(':date', new \DateTime('now'));
        }

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );
        
        if($loggedUser = $this->getUser())
        {
            $observe = $em->getRepository('AppBundle:ProfileObserve')->findBy(array(
                'targetUser' => $user,
                'user' => $loggedUser
            ));
        }

        return array(
            'user' => $user,
            'pagination' => $pagination,
            'role' => $this->getRoles($user->getRoles()),
            'observe' => isset($observe) ? $observe : false
        );
    }

    /**
     * @Route("/profile/contact/{id}", name="profile_contact")
     * @Template()
     */
    public function contactAction($id, Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->findUserBy(array(
            'id' => $id
        ));
        if(!$user)
            throw $this->createNotFoundException();

        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('profile_contact', array('id' => $id)))
            ->add('fullname', TextType::class, array(
                'label' => 'user.imie_i_nazwisko'
            ))->add('email', EmailType::class, array(
                'label' => 'kontakt.adres_email',
                'constraints' => array(new NotBlank())
            ))->add('phone', TextType::class, array(
                'label' => 'user.numer_telefonu'
            ))->add('content', TextareaType::class, array(
                'label' => 'kontakt.wiadomosc',
                'constraints' => array(new NotBlank())
            ))->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            try
            {
                $message = \Swift_Message::newInstance()
                    ->setSubject($this->get('translator')->trans('mail.kontakt_ze_strony'))
                    ->setFrom($form->get('email')->getData())
                    ->setTo($user->getEmail())
                    ->setBody(
                        $this->renderView('AppBundle:Emails:profile.html.twig', array(
                            'email' => $form->get('email')->getData(),
                            'phone' => $form->get('phone')->getData(),
                            'fullname' => $form->get('fullname')->getData(),
                            'content' => $form->get('content')->getData(),
                            'user' => $user
                        )),
                        'text/html'
                    );
                $this->get('mailer')->send($message);

                $message = \Swift_Message::newInstance()
                    ->setSubject($this->get('translator')->trans('mail.kontakt_ze_strony'))
                    ->setFrom($form->get('email')->getData())
                    ->setTo($this->getParameter('contact_address_from'))
                    ->setBody(
                        $this->renderView('AppBundle:Emails:profile.html.twig', array(
                            'email' => $form->get('email')->getData(),
                            'phone' => $form->get('phone')->getData(),
                            'fullname' => $form->get('fullname')->getData(),
                            'content' => $form->get('content')->getData(),
                            'user' => $user
                        )),
                        'text/html'
                    );
                $this->get('mailer')->send($message);
                $this->addFlash('success', $this->get('translator')->trans('mail.wyslany'));
            }
            catch(\Exception $e)
            {
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

    /**
     * @Route("/{slug}/{id}/looking", name="profile_artists_looking", requirements={
     *  "id": "\d+",
     *  "slug": "[a-zA-Z0-9\-_]+"
     * })
     * @Template()
     */
    public function profileLookingAction($id, Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');
        $em = $this->getDoctrine()->getManager();

        $user = $userManager->findUserBy(array('id'=>$id));
        if(!$user)
            throw $this->createNotFoundException ();

        if(!$user->hasRole('ROLE_SUPER_ADMIN') &&
            !$user->hasRole('ROLE_ADMIN') &&
            !$user->hasRole('ROLE_DOM_AUKCYJNY') &&
            !$user->hasRole('ROLE_GALERIA'))
        {
            throw $this->createNotFoundException();
        }

        $looking = $em->getRepository('AppBundle:UserLooking')->createQueryBuilder('ul')
            ->join('ul.target', 't')->addSelect('t')
            ->where('ul.user = :user')
            ->setParameter(':user', $user)
            ->getQuery()->getResult();

        if($loggedUser = $this->getUser())
        {
            $observe = $em->getRepository('AppBundle:ProfileObserve')->findBy(array(
                'targetUser' => $user,
                'user' => $loggedUser
            ));
        }

        return array(
            'user' => $user,
            'looking' => $looking,
            'role' => $this->getRoles($user->getRoles()),
            'observe' => isset($observe) ? $observe : false
        );
    }

    /**
     * @Route("/{prefix}/{slug}/agents", name="profile_agents", requirements={
     *  "prefix": "(profile|artists|redactor|galleries|auction-houses)",
     *  "slug": "[a-zA-Z0-9\-_]+"
     * })
     * @Template()
     */
    public function profileAgentsAction($slug, Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');
        $em = $this->getDoctrine()->getManager();

        $user = $userManager->findUserBy(array('profileSlug'=>$slug));
        if(!$user)
            throw $this->createNotFoundException ();

        if(!$user->hasRole('ROLE_ARTYSTA') && !$user->hasRole('ROLE_ADMIN') && !$user->hasRole('ROLE_SUPER_ADMIN'))
            throw $this->createNotFoundException();


        $agentsQb = $em->getRepository('AppBundle:Work')->createQueryBuilder('w');
        $agentsQb->join('w.author', 'a')->addSelect('a')
            ->where('w.artist = :artist')
            ->andWhere($agentsQb->expr()->like('a.roles', ':role_a') . ' OR ' . $agentsQb->expr()->like('a.roles', ':role_d'))
            ->groupBy('a.id')
            ->setParameter(':artist', $user->getFullname())
            ->setParameter(':role_a', '%ROLE_GALERIA%')
            ->setParameter(':role_d', '%ROLE_DOM_AUKCYJNY%');

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $agentsQb,
            $request->query->getInt('page', 1),
            10
        );

        if($loggedUser = $this->getUser())
        {
            $observe = $em->getRepository('AppBundle:ProfileObserve')->findBy(array(
                'targetUser' => $user,
                'user' => $loggedUser
            ));
        }

        return array(
            'user' => $user,
            'role' => $this->getRoles($user->getRoles()),
            'pagination' => $pagination,
            'observe' => isset($observe) ? $observe : false
        );
    }

    /**
     * @Route("/{prefix}/{slug}/auctions-results", name="profile_auctions_results", requirements={
     *  "prefix": "(profile|artists|redactor|galleries|auction-houses)",
     *  "slug": "[a-zA-Z0-9\-_]+"
     * })
     * @Template()
     */
    public function profileAuctionsResultsAction($slug, Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');
        $em = $this->getDoctrine()->getManager();

        $user = $userManager->findUserBy(array('profileSlug'=>$slug));
        if(!$user)
            throw $this->createNotFoundException ();

        if(!$user->hasRole('ROLE_ARTYSTA') && !$user->hasRole('ROLE_ADMIN') && !$user->hasRole('ROLE_SUPER_ADMIN'))
            throw $this->createNotFoundException();

        $results = $em->getRepository('AppBundle:AuctionWork')->createQueryBuilder('aw')
            ->join('aw.auction', 'a')
            ->join('aw.work', 'w')->addSelect('w')
            ->leftJoin('aw.bids', 'b')->addSelect('b')
            ->where('w.author = :user OR w.artist = :artist')
            ->andWhere('a.endDate < :date')
            ->setParameter(':date', new \DateTime('now'))
            ->setParameter(':artist', $user->getFullname())
            ->setParameter(':user', $user);

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $results,
            $request->query->getInt('page', 1),
            10
        );

        if($loggedUser = $this->getUser())
        {
            $observe = $em->getRepository('AppBundle:ProfileObserve')->findBy(array(
                'targetUser' => $user,
                'user' => $loggedUser
            ));
        }

        return array(
            'user' => $user,
            'role' => $this->getRoles($user->getRoles()),
            'pagination' => $pagination,
            'observe' => isset($observe) ? $observe : false
        );
    }

    /**
     * @Route("/{prefix}/{slug}/exhibitions", name="profile_exhibitions", requirements={
     *  "prefix": "(profile|artists|redactor|galleries|auction-houses|museum)",
     *  "slug": "[a-zA-Z0-9\-_]+"
     * })
     * @Template()
     */
    public function profileExhibitionsAction($slug, Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');
        $em = $this->getDoctrine()->getManager();

        $user = $userManager->findUserBy(array('profileSlug'=>$slug));
        if(!$user)
            throw $this->createNotFoundException ();

        if(!$user->hasRole('ROLE_GALERIA') && !$user->hasRole('ROLE_MUZEUM') && !$user->hasRole('ROLE_ADMIN') && !$user->hasRole('ROLE_SUPER_ADMIN'))
            throw $this->createNotFoundException();

        $results = $em->getRepository('AppBundle:Exhibition')->createQueryBuilder('e')
            ->select('e.id, e.image, e.startDate, e.endDate, e.approved, e.address, e.city')
            ->join('e.langs', 'l')->addSelect('l.title, l.description')
            ->join('l.lang', 'gl')
            ->andWhere('gl.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale())
//            ->andWhere('l.exhibition = :id')
//            ->setParameter(':id', 'e.id')
            ->andWhere('e.author = :user')
            ->andWhere('e.approved = :approved')
            ->setParameter(':user', $user)
            ->setParameter(':approved', true);

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $results,
            $request->query->getInt('page', 1),
            10
        );

        if($loggedUser = $this->getUser())
        {
            $observe = $em->getRepository('AppBundle:ProfileObserve')->findBy(array(
                'targetUser' => $user,
                'user' => $loggedUser
            ));
        }

        return array(
            'user' => $user,
            'role' => $this->getRoles($user->getRoles()),
            'pagination' => $pagination,
            'observe' => isset($observe) ? $observe : false
        );
    }

    /**
     * @Route("/{prefix}/{slug}/fair", name="profile_fair", requirements={
     *  "prefix": "(profile|galleries)",
     *  "slug": "[a-zA-Z0-9\-_]+"
     * })
     * @Template()
     */
    public function profileFairAction($slug, Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');
        $em = $this->getDoctrine()->getManager();

        $user = $userManager->findUserBy(array('profileSlug'=>$slug));
        if(!$user)
            throw $this->createNotFoundException ();

        if(!$user->hasRole('ROLE_GALERIA') && !$user->hasRole('ROLE_ADMIN') && !$user->hasRole('ROLE_SUPER_ADMIN'))
            throw $this->createNotFoundException();

        $targCategory = $em->getRepository('AppBundle:EventCategory')->find(7);
        $results = $em->getRepository('AppBundle:Event')->createQueryBuilder('e')
            ->join('e.users', 'u', 'WITH', 'u.id = :user')
            ->join('e.langs', 'el')->addSelect('el.title, el.description')
            ->join('el.lang', 'l')
            ->where('e.category = :category')
            ->andWhere('l.shortcut = :shortcut')
            ->setParameter(':shortcut', $request->getLocale())
            ->setParameter(':user', $user)
            ->setParameter(':category', $targCategory);

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $results,
            $request->query->getInt('page', 1),
            10
        );

        if($loggedUser = $this->getUser())
        {
            $observe = $em->getRepository('AppBundle:ProfileObserve')->findBy(array(
                'targetUser' => $user,
                'user' => $loggedUser
            ));
        }

        return array(
            'user' => $user,
            'role' => $this->getRoles($user->getRoles()),
            'pagination' => $pagination,
            'observe' => isset($observe) ? $observe : false
        );
    }

    public function userDetailsOnWorkPageAction($artist_id, $seller_id)
    {
        $userManager = $this->get('fos_user.user_manager');

        if($seller_id == $artist_id)
        {
            return $this->render('@User/Default/userWorkDetails.html.twig', [
                'artist' => $userManager->findUserBy([
                    'id' => $artist_id
                ]),
                'seller' => null
            ]);
        }

        return $this->render('@User/Default/userWorkDetails.html.twig', [
            'artist' => $artist_id ? $userManager->findUserBy([
                'id' => $artist_id
            ]) : null,
            'seller' => $userManager->findUserBy([
                'id' => $seller_id
            ])
        ]);

    }

    private function getRoles(array $roles)
    {
        $role = null;
        if(in_array('ROLE_ARTYSTA', $roles)) { $role = $this->get('translator')->trans('roles.artysta'); }
        else if(in_array('ROLE_DOM_AUKCYJNY', $roles)) { $role = $this->get('translator')->trans('roles.dom_aukcyjny'); }
        else if(in_array('ROLE_EDYTOR', $roles)) { $role = $this->get('translator')->trans('roles.edytor'); }
        else if(in_array('ROLE_REDAKTOR', $roles)) { $role = $this->get('translator')->trans('roles.redaktor'); }
        else if(in_array('ROLE_GALERIA', $roles)) { $role = $this->get('translator')->trans('roles.galeria'); }
        else if(in_array('ROLE_ADMIN', $roles) || in_array('ROLE_SUPER_ADMIN', $roles)) { $role = $this->get('translator')->trans('roles.administrator'); }
        return $role;
    }

}
