<?php

namespace AdminBundle\Controller;

use AppBundle\Entity\EventCategory;
use AppBundle\Entity\EventCategoryLang;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use AppBundle\Entity\Event;
use AdminBundle\Form\EventType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;


use AdminBundle\Form\NewsType;
use AdminBundle\Form\NewsCategoryType;
use AppBundle\Entity\News;
use AppBundle\Entity\NewsCategory;
use Cocur\Slugify\Slugify;

/**
 * @Route("/admin/events")
 */
class EventsController extends Controller
{
    /**
     * @Route("/", name="admin_events_index")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        
        $query = $em->getRepository('AppBundle:Event')->createQueryBuilder('e')
            ->select('e')
            ->join('e.langs', 'l')->addSelect('l.title, l.description')
            ->join('l.lang', 'lg')
            ->groupBy('l.event');
        
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
     * @Route("/add", name="admin_events_add")
     */
    public function addAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            try {
                
                $file = $event->getImage();
                if($file instanceof UploadedFile)
                {
                    $fileName = $this->get('app.uploader')->upload($file, $this->getParameter('event_files_directory'));
                    $event->setImage($fileName);
                }

                foreach($event->getLangs() as $lang)
                    $lang->setEvent($event);
                
                $em->persist($event);
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('wydarzenia.dodane', [], 'admin'));

                foreach($event->getUsers() as $user)
                {
                    $usersObserving = $em->getRepository('AppBundle:ProfileObserve')->createQueryBuilder('po')
                        ->join('po.user', 'u')->addSelect('u')
                        ->where('po.targetUser = :user')
                        ->setParameter(':user', $user)
                        ->getQuery()->getResult();
                    foreach($usersObserving as $obUser)
                    {
                        $message = \Swift_Message::newInstance()
                            ->setSubject($this->get('translator')->trans('notifications.user_wydarzenie'))
                            ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
                            ->setTo($obUser->getUser()->getEmail())
                            ->setBody(
                                $this->renderView(
                                    '@App/Emails/notification_user_exhibition.html.twig',
                                    array(
                                        'event' => $event,
                                        'user' => $obUser->getUser(),
                                        'target' => $user,
                                        'lang' => $obUser->getUser()->getCountry() == 'Polska' || $obUser->getUser()->getCountry() == 'Poland' ? 'pl' : 'en'
                                    )
                                ),
                                'text/html'
                            )
                        ;
                        $this->get('mailer')->send($message);
                    }
                }

                return new RedirectResponse($this->generateUrl('admin_events_index'));
            } catch(\Exception $e)
            {
                $this->addFlash('error', $this->get('translator')->trans('wydarzenia.nie_dodane', [], 'admin').$e->getMessage());
            }
        }
        
        return $this->render('AdminBundle:Events:add_edit.html.twig', array(
            'form' => $form->createView()
        ));
    }
    
    /**
     * @Route("/edit/{id}", name="admin_events_edit", requirements={
     *  "id": "\d+"
     * })
     */
    public function editAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $event = $em->getRepository('AppBundle:Event')->createQueryBuilder('e')
            ->select('e')
            ->join('e.langs', 'l')->addSelect('l.title, l.description')
            ->join('l.lang', 'lg')
            ->where('e.id = :id')
            ->setParameter(':id', $id)
            ->groupBy('l.event')->getQuery()->getSingleResult();
        if(!$event)
            throw $this->createNotFoundException ();
        
        $image = $event[0]->getImage();
        if($image && file_exists($this->getParameter('event_files_directory').'/'.$image))
            $event[0]->setImage(new File($this->getParameter('event_files_directory').'/'.$image));
        else $event[0]->setImage(null);
        
        $form = $this->createForm(EventType::class, $event[0]);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            try {
                
                $file = $event[0]->getImage();
                if($file instanceof UploadedFile)
                {
                    $fileName = $this->get('app.uploader')->upload($file, $this->getParameter('event_files_directory'));
                    $event[0]->setImage($fileName);
                } else {$event[0]->setImage($image);}

                foreach($event[0]->getLangs() as $lang)
                    $lang->setEvent($event[0]);
                
                $em->persist($event[0]);
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('wydarzenia.dodane', [], 'admin'));
                return new RedirectResponse($this->generateUrl('admin_events_index'));
            } catch(\Exception $e)
            {
                $this->addFlash('error', 'Wydarzenie nie mogło zostać zmienione.'.$e->getMessage());
                return new RedirectResponse($this->generateUrl('admin_events_edit', array(
                    'id' => $id
                )));
            }
        }
        
        return $this->render('AdminBundle:Events:add_edit.html.twig', array(
            'form' => $form->createView(),
            'event' => $event
        ));
    }
    
    /**
     * @Route("/delete/{id}", name="admin_events_delete", requirements={
     *  "id": "\d+"
     * })
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        
        $event = $em->getRepository('AppBundle:Event')->find($id);
        if(!$event)
            throw $this->createNotFoundException ();
        
        $em->remove($event);
        $em->flush();
        $this->addFlash('success', $this->get('translator')->trans('wydarzenia.usuniete', [], 'admin'));
        
        return new RedirectResponse($this->generateUrl('admin_events_index'));
    }

    /**
     * KATEGORIE
     */

    /**
     * @Route("/categories", name="admin_events_categories")
     * @Template()
     */
    public function categoriesAction()
    {
        $em = $this->getDoctrine()->getManager();
        $categories = $em->getRepository('AppBundle:EventCategoryLang')->createQueryBuilder('cl')
            ->select('cl, c, l')
            ->join('cl.event', 'c')
            ->join('cl.lang', 'l')
            ->groupBy('cl.event')
            ->getQuery()->getResult();

        return array(
            'categories' => $categories
        );
    }

    /**
     * @Route("/categories/langs/{id}", name="admin_events_categories_languages")
     * @Template()
     */
    public function categoryLanguagesAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $category = $em->getRepository('AppBundle:EventCategory')->find($id);
        if(!$category)
            throw $this->createNotFoundException();

        $langs = $em->getRepository('AppBundle:EventCategoryLang')->findBy(array(
            'event' => $category
        ));

        return array(
            'langs' => $langs,
            'category' => $category
        );
    }

    /**
     * @Route("/categories/langs/{id}/add", name="admin_events_categories_languages_add")
     */
    public function categoryLanguagesAddAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $category = $em->getRepository('AppBundle:EventCategory')->find($id);
        if(!$category)
            throw $this->createNotFoundException();

        $newsCategoryLang = new EventCategoryLang();
        $newsCategoryLang->setEvent($category);
        $form = $this->createFormBuilder($newsCategoryLang)
            ->add('title', TextType::class, array(
                'label' => 'main.tytul'
            ))->add('lang', EntityType::class, array(
                'label' => 'admin.jezyk',
                'translation_domain' => 'admin',
                'class' => 'AppBundle:Language',
                'choice_label' => 'shortcut'
            ))->getForm();

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            try {
                $em->persist($newsCategoryLang);
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('aktualnosci.kategorie.jezyk_dodany', array(), 'admin'));
                return new RedirectResponse($this->generateUrl('admin_events_categories_languages', array(
                    'id' => $id
                )));
            } catch(\Exception $e)
            {
                $this->addFlash('error', $this->get('translator')->trans('aktualnosci.kategorie.jezyk_nie_dodany', array(), 'admin'));
            }
        }

        return $this->render('AdminBundle:Events:categories_add_edit.html.twig', array(
            'form' => $form->createView(),
            'category' => $category
        ));
    }

    /**
     * @Route("/categories/add", name="admin_events_categories_add")
     */
    public function addCategoryAction(Request $request)
    {
        $newsCategoryLang = new EventCategoryLang();
        $form = $this->createFormBuilder($newsCategoryLang)
            ->add('title', TextType::class, array(
                'label' => 'main.tytul'
            ))->add('lang', EntityType::class, array(
                'label' => 'admin.jezyk',
                'translation_domain' => 'admin',
                'class' => 'AppBundle:Language',
                'choice_label' => 'shortcut'
            ))->getForm();

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            try {
                $em = $this->getDoctrine()->getManager();
                $category = new EventCategory();
                $em->persist($category);

                $newsCategoryLang->setEvent($category);
                $em->persist($newsCategoryLang);

                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('aktualnosci.kategorie.dodana', array(), 'admin'));
                return new RedirectResponse($this->generateUrl('admin_events_categories'));
            } catch(\Exception $e)
            {
                $this->addFlash('error', $this->get('translator')->trans('aktualnosci.kategorie.nie_dodana', array(), 'admin'));
            }
        }

        return $this->render('AdminBundle:Events:categories_add_edit.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/categories/langs/delete/{id}", name="admin_events_categories_lang_delete", requirements={
     *  "id": "\d+"
     * })
     */
    public function deleteLanguageCategoryAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $lang = $em->getRepository('AppBundle:EventCategoryLang')->find($id);
        if(!$lang)
            throw $this->createNotFoundException();

        $em->remove($lang);
        $em->flush();

        $lastRoute = $this->get('session')->get('last_route');
        if (!empty($lastRoute) && !empty($lastRoute['name'])) {
            return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
        } else {
            return new RedirectResponse(($this->generateUrl('homepage')));
        }
    }

    /**
     * @Route("/categories/delete/{id}", name="admin_events_categories_delete", requirements={
     *  "id": "\d+"
     * })
     */
    public function deleteCategoryAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $category = $em->getRepository('AppBundle:EventCategory')->find($id);
        if (!$category)
            throw $this->createNotFoundException();

        try {
            $em->remove($category);
            $em->flush();
            $this->addFlash('success', $this->get('translator')->trans('aktualnosci.kategorie.usunieta', array(), 'admin'));
        } catch (\Exception $e)
        {
            $this->addFlash('error', $this->get('translator')->trans('aktualnosci.kategorie.nie_usunieta', array(), 'admin'));
        }
        return new RedirectResponse($this->generateUrl('admin_events_categories'));
    }

    /**
     * @Route("/categories/edit/{id}", name="admin_events_categories_edit", requirements={
     *  "id": "\d+"
     * })
     */
    public function editCategoryAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $category = $em->getRepository('AppBundle:EventCategory')->find($id);
        if (!$category)
            throw $this->createNotFoundException();

        $form = $this->createForm(NewsCategoryType::class, $category);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            try {
                $em = $this->getDoctrine()->getManager();
                $em->persist($category);
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('aktualnosci.kategorie.zmieniona', array(), 'admin'));
                return new RedirectResponse($this->generateUrl('admin_events_categories'));
            }
            catch(\Exception $e)
            {
                $this->addFlash('error', $this->get('translator')->trans('aktualnosci.kategorie.nie_zmieniona', array(), 'admin'));
            }
        }

        return $this->render('AdminBundle:Events:categories_add_edit.html.twig', array(
            'form' => $form->createView(),
            'category' => $category
        ));
    }
    
}
