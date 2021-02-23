<?php

namespace AdminBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use FOS\UserBundle\Model\UserManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Cocur\Slugify\Slugify;
use Psr\Log\LoggerInterface;

use AdminBundle\Form\UserType;

/**
 * @Route("/admin")
 */
class UsersController extends Controller
{
    /**
     * @Route("/users", name="admin_users")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $filter = $this->get('admin.filter');
        $filter->setConfiguration(array(
            'username' => array(
                'name' => 'Login',
                'type' => 'string'
            ),
            'email' => array(
                'name' => 'E-mail',
                'type' => 'string'
            ),
            'fullname' => array(
                'name' => 'Imię i nazwisko',
                'type' => 'string'
            )
        ));
        $query = $filter->parseQuery($em->getRepository('UserBundle:User')->createQueryBuilder('u'), 'u');

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            25
        );

        return array(
            'pagination' => $pagination,
            'filters' => $this->renderView('AdminBundle::filters.html.twig', array(
                'filters' => $filter->getFilters()
            ))
        );
    }

    
    /**
     * @Route("/users/edit/{id}", name="admin_users_edit", requirements={
     *  "id": "\d+"
     * })
     * @Template()
     */
    public function editAction(Request $request, $id)
    {
        /** @var LoggerInterface logger */
        $logger = $this->get('logger');
        $logger->info('Start Controller: '.__CLASS__. ' Action: '.__FUNCTION__);

        $userManager = $this->get('fos_user.user_manager');
        $em = $this->getDoctrine()->getManager();
        /** @var User $user */
        $user = $userManager->findUserBy(array('id' => $id));
        if(!$user)
            throw $this->createNotFoundException();

        $image = $user->getImage();
        if($image && file_exists($this->getParameter('user_files_directory').'/'.$image))
            $user->setImage(new File($this->getParameter('user_files_directory').'/'.$image));

        $langs = $em->getRepository('AppBundle:Language')->findAll();
        $cards = array();
        foreach($langs as $lang)
            $cards[] = array(
                'lang' => $lang->getShortcut(),
                'content' => ''
            );
        $result = (array)$user->getCard() + $cards;
        $user->setCard($result);

        $slugify = new Slugify();
        if(empty($user->getSlug()))
        {
            $logger->info('Start slug creation');
            $slug = '';
            if(!empty($user->getFullname())) {
                $slug = $slugify->slugify( $user->getFullname() );
            } elseif(!empty($user->getUsername())) {
                $slug = $slugify->slugify( $user->getUsername() );
            }
            $user->setSlug($slug);
            $logger->info('Created slug: '.$slug);
        }
        $form = $this->createForm(UserType::class, $user, array(
            'roles' => $this->getParameter('security.role_hierarchy.roles')
        ));
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $logger->info('Form is submited and Valid');
            $file = $user->getImage();
            if($file instanceof UploadedFile)
            {
                $image = $this->get('app.uploader')->upload($file, $this->getParameter('user_files_directory'));
            }
            $user->setImage($image);

            if(empty($user->getSlug()))
            {
                $logger->info('Start slug creation after validation');
                if(!empty($user->getFullname())) {
                    $slug = $slugify->slugify( $user->getFullname() );
                } elseif(!empty($user->getUsername())) {
                    $slug = $slugify->slugify( $user->getUsername() );
                }
                $user->setSlug($slug);
                $logger->info('Created slug: '.$slug);
            }
            else{
                $slug = $slugify->slugify( $user->getSlug() );
            }
            /** @var EntityManager $em */
            $em = $this->getDoctrine()->getManager();
            /** @var QueryBuilder $query */
            $query = $em->getRepository('UserBundle:User')
                ->createQueryBuilder('u')
                ->select('count( u.id )')
                ->where('u.profileSlug Like :new_slug' )
                ->andWhere('u.id <> :new_id' );
            $query->setParameter('new_id', $user->getId());
            $is_url = true;
            $logger->info('Start check slug');
            do {
                $logger->info('Checking slug:'.$slug);
                $query->setParameter('new_slug', $slug);
                $urls_count = $query->getQuery()->getSingleResult();
                if( !isset( $urls_count[1] )
                    || ( (int)$urls_count[1] == 0 )
                ) {
                    $is_url = false;
                    break;
                }
                else
                {
                    $slug .= '-'.$urls_count[1];
                }
            } while( $is_url );
            $user->setProfileSlug( $slug );

            $userManager->updateUser($user);
            $this->addFlash('success', $this->get('translator')->trans('uzytkownicy.zmieniony', [], 'admin'));
            return new RedirectResponse($this->generateUrl('admin_users'));
        }
        
        return $this->render('AdminBundle:Users:add_edit.html.twig', array(
            'user' => $user,
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/users/generate-slugs/", name="admin_users_generate_slugs")
     * @Template()
     */
    public function generateSlugsAction(Request $request)
    {
        /** @var LoggerInterface logger */
        $logger = $this->get('logger');
        $logger->info('Start Controller: '.__CLASS__. ' Action: '.__FUNCTION__);
        /** @var UserManager $user */
        $userManager = $this->get('fos_user.user_manager');
        /** @var User $user */
        $users = $userManager->findUsers();
        $slugify = new Slugify();
        foreach( $users as $user) {
            if (empty($user->getSlug())) {
                $logger->info('Start slug creation');
                $slug = '';
                if (!empty($user->getFullname())) {
                    $slug = $slugify->slugify($user->getFullname());
                } elseif (!empty($user->getUsername())) {
                    $slug = $slugify->slugify($user->getUsername());
                }
                $user->setSlug($slug);
                /** @var EntityManager $em */
                $em = $this->getDoctrine()->getManager();
                /** @var QueryBuilder $query */
                $query = $em->getRepository('UserBundle:User')
                    ->createQueryBuilder('u')
                    ->select('count( u.id )')
                    ->where('u.profileSlug Like :new_slug')
                    ->andWhere('u.id <> :new_id');
                $query->setParameter('new_id', $user->getId());
                $is_url = true;
                $logger->info('Start check slug');
                do {
                    $logger->info('Checking slug:' . $slug);
                    $query->setParameter('new_slug', $slug);
                    $urls_count = $query->getQuery()->getSingleResult();
                    if (!isset($urls_count[1])
                        || ((int)$urls_count[1] == 0)
                    ) {
                        $is_url = false;
                        break;
                    } else {
                        $slug .= '-' . $urls_count[1];
                    }
                } while ($is_url);
                $user->setProfileSlug($slug);

                $userManager->updateUser($user);
            }
        }
        $this->addFlash('success', $this->get('translator')->trans('created all slug', [], 'admin'));
        return new RedirectResponse($this->generateUrl('admin_users'));
    }


    /**
     * @Route("/users/delete/{id}", name="admin_users_delete", requirements={
     *  "id": "\d+"
     * })
     */
    public function deleteAction($id)
    {
        $userManager = $this->get('fos_user.user_manager');

        $user = $userManager->findUserBy(array('id' => $id));
        if(!$user)
            throw $this->createNotFoundException();
          
        try { 
            
//            // delete dependencies                                
            $auctionBidId = $this->findAuctionBidId($id);

            foreach($auctionBidId as $key => $value) {
                $this->deleteActionHelper($value);
            }
            $userManager->deleteUser($user);
            
            $this->addFlash('success', $this->get('translator')->trans('uzytkownicy.usuniety', [], 'admin'));
            
        } catch(\Exception $e)
        {
            $this->addFlash('error', 'Występują powiązania dla użytownika których nie udało się usunąć. '.$e->getMessage());
        }
        return new RedirectResponse($this->generateUrl('admin_users'));
    }
    
    // auction BId
    public function deleteActionHelper($auctionBidId) {
        $em = $this->getDoctrine()->getManager();
        $query = $em->getRepository('AppBundle:AuctionBid')->find($auctionBidId);
        $query->setAuthor(null);
        $em->flush();
    }
    
    public function findAuctionBidId($id) {
        $em = $this->getDoctrine()->getManager();
        $query = $em->getRepository('AppBundle:AuctionBid')->createQueryBuilder('a')
                ->select('a.id')
                ->where('a.author = :author')
                ->setParameter(':author', $id)
                ->getQuery()->getResult();        
        
        return $query;
    }
    
    
    /**
     * @Route("/users/disable/{id}", name="admin_users_disable", requirements={
     *  "id": "\d+"
     * })
     */
    public function disableUser($id) {
        $em = $this->getDoctrine()->getManager();
        
        $user = $em->getRepository('UserBundle:User')->find($id);
        
        $user->setEnabled(0);        
        $em->flush();
        
        $this->addFlash('success', $this->get('translator')->trans('uzytkownicy.wylaczony', [], 'admin'));
        
        return new RedirectResponse($this->generateUrl('admin_users'));
    }
    
    /**
     * @Route("/users/enable/{id}", name="admin_users_enable", requirements={
     *  "id": "\d+"
     * })
     */
    public function enableUser($id) {
        $em = $this->getDoctrine()->getManager();
        
        $user = $em->getRepository('UserBundle:User')->find($id);
        
        $user->setEnabled(1);        
        $em->flush();
        
        $this->addFlash('success', $this->get('translator')->trans('uzytkownicy.wlaczony', [], 'admin'));
        
        return new RedirectResponse($this->generateUrl('admin_users'));
    }
    
    
    /**
     * @Route("/users/add", name="admin_users_add")
     * @Template()
     */
    public function addAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->createUser();
        $user->setImage(null);

        $langs = $em->getRepository('AppBundle:Language')->findAll();
        $cards = array();
        foreach($langs as $lang)
            $cards[] = array(
                'lang' => $lang->getShortcut(),
                'content' => ''
            );
        $user->setCard($cards);

        $plainPass = str_shuffle('abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ123456789123456789!@#$%^&*!@#$%^&*');
        $plainPass = substr($plainPass, 0, 10);

        $form = $this->createForm(UserType::class, $user, array(
            'roles' => $this->getParameter('security.role_hierarchy.roles')
        ));
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $file = $user->getImage();
            if($file instanceof UploadedFile)
            {
                $fileName = $this->get('app.uploader')->upload($file, $this->getParameter('user_files_directory'));
                $user->setImage($fileName);
            }

            $user->setPassword($request->request->get('password'));
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', $this->get('translator')->trans('uzytkownicy.dodany', [], 'admin'));
            return new RedirectResponse($this->generateUrl('admin_users'));
        }
        
        return $this->render('AdminBundle:Users:add_edit.html.twig', array(
            'form' => $form->createView(),
            'plainPass' => $plainPass
        ));
    }
    
    /**
     * @Route("/users/added", name="admin_users_added")
     * @Template()
     */
    public function addedAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $query = $em->getRepository('UserBundle:User')->createQueryBuilder('u')
            ->join('u.creator', 'c')->addSelect('c')
                ->where('u.creator IS NOT NULL');
        $paginator  = $this->get('knp_paginator');
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
     * @Route("/users/search", name="admin_users_search_username")
     */
    public function searchUsernameAction(Request $request) 
    {
        $response = new JsonResponse();
        $q = $request->get('term');

        $em = $this->getDoctrine()->getManager();

        $qb = $em->getRepository('UserBundle:User')->createQueryBuilder('u');
        $data = $qb->select('u.username, u.fullname')
            ->where($qb->expr()->like('u.fullname', ':q'))
            ->setParameter(':q', '%'.$q.'%')
            ->getQuery()->getResult();

        if(!$data)
            return false;

        $return = array();
        foreach($data as $value)
        {
            $return[] = array(
                'id' => $value['username'],
                'label' => $value['fullname'],
                'value' => $value['username'],
            );
        }

        $response->setData($return);
        return $response;
    }

    /**
     * @Route("/users/generatePass/{id}", name="admin_users_pass", requirements={
     *  "id": "\d+"
     * })
     */
    public function generatePassAction($id)
    {
        $userManager = $this->get('fos_user.user_manager');

        $user = $userManager->findUserBy(array('id' => $id));
        if(!$user)
            throw $this->createNotFoundException();

        $plainPass = str_shuffle('abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ123456789123456789!@#$%^&*!@#$%^&*');
        $plainPass = substr($plainPass, 0, 10);

        $user->setPlainPassword($plainPass);
        $userManager->updateUser($user);

        $this->addFlash('success', 'Aktualne hasło dla konta '.$user->getFullname().' ('.$user->getUsername().') to '.$plainPass);

        $lastRoute = $this->get('session')->get('last_route');
        if (!empty($lastRoute) && !empty($lastRoute['name'])) {
            return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
        } else {
            return new RedirectResponse(($this->generateUrl('homepage')));
        }
    }

}
