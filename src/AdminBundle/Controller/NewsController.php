<?php

namespace AdminBundle\Controller;

use AppBundle\Entity\NewsCategoryLang;
use AppBundle\Entity\NewsLang;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use AdminBundle\Form\NewsType;
use AdminBundle\Form\NewsCategoryType;
use AppBundle\Entity\News;
use AppBundle\Entity\NewsCategory;
use Cocur\Slugify\Slugify;


/**
 * @Route("/admin")
 */
class NewsController extends Controller
{
    /**
     * @Route("/news", name="admin_news")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $filter = $this->get('admin.filter');
        $filter->setConfiguration(array(
            'title' => array(
                'name' => 'Tytuł',
                'type' => 'string'
            ),
            'pinned' => array(
                'name' => 'Wyróżnione',
                'type' => 'boolean'
            ),
            'addDate' => array(
                'name' => 'Data dodania',
                'type' => 'datetimerange'
            ),
            'editDate' => array(
                'name' => 'Data edycji',
                'type' => 'datetimerange'
            )
        ));
        $query = $filter->parseQuery($em->getRepository('AppBundle:News')->createQueryBuilder('n'), 'n');
        $query->orderBy('n.id', 'desc')
            ->select('n')
            ->addSelect('l')
            ->join('n.langs', 'l')
            ->groupBy('l.news');
        $articles = $query->getQuery()->getResult();

        return array(
            'articles' => $articles,
            'filters' => $this->renderView('AdminBundle::filters.html.twig', array(
                'filters' => $filter->getFilters()
            ))
        );
    }

    /**
     * @Route("/news/add", name="admin_news_add")
     */
    public function addAction(Request $request)
    {
        $newsLang = new NewsLang();
        $newsLang->setTitle('');
        $newsLang->setMetaTitle('');

        $news = new News();
        $news->addLang($newsLang);
        $news->setAuthor($this->getUser());
        $form = $this->createForm(NewsType::class, $news, array(
            'role' => $this->getUser()->getRoles()
        ));

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            try {
                $em = $this->getDoctrine()->getManager();

                $file = $news->getImage();
                if($file instanceof UploadedFile)
                {
                    $fileName = $this->get('admin.uploader')->upload($file);
                    $news->setImage($fileName);
                }

                foreach ($news->getLangs() as $lang) {
                    $lang->setNews($news);
                }
                $slug =  $news->getSlug();
                $slugify = new Slugify();
                if( empty( $slug ) )
                {
                    $slug = $slugify->slugify( $news->getObjectTitle( $request->getLocale()  ) );
                }
                $news->setSlug( $em->getRepository('AppBundle:News')->checkSlug( $slug, $news->getId()));

                //)
                $em->persist($news);
                $em->flush();

                foreach($news->getUsers() as $user)
                {
                    $usersObserving = $em->getRepository('AppBundle:ProfileObserve')->createQueryBuilder('po')
                        ->join('po.user', 'u')
                        ->where('po.targetUser = :user')
                        ->setParameter(':user', $user)
                        ->getQuery()->getResult();
                    foreach($usersObserving as $obUser)
                    {
                        $message = \Swift_Message::newInstance()
                            ->setSubject($this->get('translator')->trans('notifications.user_news'))
                            ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
                            ->setTo($obUser->getEmail())
                            ->setBody(
                                $this->renderView(
                                    '@App/Emails/notification_user_news.html.twig',
                                    array(
                                        'news' => $news,
                                        'user' => $obUser,
                                        'target' => $user,
                                        'lang' => $obUser->getCountry() == 'Polska' || $obUser->getCountry() == 'Poland' ? 'pl' : 'en'
                                    )
                                ),
                                'text/html'
                            )
                        ;
                        $this->get('mailer')->send($message);
                    }
                }

                $this->addFlash('success', $this->get('translator')->trans('aktualnosci.dodany', array(), 'admin'));
                return new RedirectResponse($this->generateUrl('admin_news'));
            }
            catch(\Exception $e)
            {
                $this->addFlash('error', $this->get('translator')->trans('aktualnosci.nie_dodany', array(), 'admin') . $e->getMessage());
            }
        }

        return $this->render('AdminBundle:News:add_edit.html.twig', array(
            'form' => $form->createView()
        ));
    }
    /**
     * @Route("/news/generate-slugs/", name="admin_news_generate_slugs")
     * @Template()
     */
    public function generateSlugsAction(Request $request)
    {
        /** @var LoggerInterface logger */
        $logger = $this->get('logger');
        $logger->info('Start Controller: '.__CLASS__. ' Action: '.__FUNCTION__);
        /** @var WorkRepository $work_repository */
        $news_repository = $this->getDoctrine()->getRepository('AppBundle:News');
        $newses = $news_repository->findAll();
        $slugify = new Slugify();
        foreach( $newses as $news) {
            $slug = $news->getSlug();
            if( empty( $slug ) )
            {
                $slug = $slugify->slugify( $news->getObjectTitle( $request->getLocale()  ) );
                $news->setSlug( $news_repository->checkSlug( $slug , $news->getId()));
                $logger->info('Slug for object #'.$news->getId().' is '.$news->getSlug());
                $news_repository->update( $news );
            }
        }
        $this->addFlash('success', $this->get('translator')->trans('created all slug', [], 'admin'));
        return new RedirectResponse($this->generateUrl('admin_news'));
    }

    /**
     * @Route("/news/edit/{id}", name="admin_news_edit", requirements={
     *  "id": "\d+"
     * })
     */
    public function editAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $article = $em->getRepository('AppBundle:News')->find($id);
        $image = $article->getImage();

        if($article->getImage() && file_exists($this->getParameter('news_files_directory').'/'.$article->getImage()))
            $article->setImage(
                new File($this->getParameter('news_files_directory').'/'.$article->getImage())
            );
        else $article->setImage(null);

        $form = $this->createForm(NewsType::class, $article, array(
            'role' => $this->getUser()->getRoles()
        ));

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $file = $article->getImage();
            if($file instanceof UploadedFile)
            {
                $fileName = $this->get('admin.uploader')->upload($file);
                $article->setImage($fileName);
            } else {
                $article->setImage($image);
            }

            foreach ($article->getLangs() as $lang) {
                $lang->setNews($article);
            }
            $slug =  $article->getSlug();
            $slugify = new Slugify();
            if( empty( $slug ) )
            {
                $slug = $slugify->slugify( $article->getObjectTitle( $request->getLocale()  ) );
            }
            $article->setSlug( $em->getRepository('AppBundle:News')->checkSlug( $slug , $article->getId() ));

            $em->persist($article);
            $em->flush();
            $this->addFlash('success', $this->get('translator')->trans('aktualnosci.zmieniony', array(), 'admin'));
            return new RedirectResponse($this->generateUrl('admin_news'));
        }

        return $this->render('AdminBundle:News:add_edit.html.twig', array(
            'form' => $form->createView(),
            'article' => $article
        ));
    }

    /**
     * @Route("/news/delete/{id}", name="admin_news_delete", requirements={
     *  "id": "\d+"
     * })
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $news = $em->getRepository('AppBundle:News')->find($id);
        if(!$news)
            throw $this->createNotFoundException();

        try {
//            foreach($news->getLangs() as $lang)
//            {
//                $em->remove($lang);
//            }
//            $em->flush();
            $em->remove($news);
            $em->flush();
            $this->addFlash('success', $this->get('translator')->trans('aktualnosci.usuniety', array(), 'admin'));
        } catch(\Exception $e)
        {
            $this->addFlash('error', 'Nie udało się usunąć historii. '.$e->getMessage());
        }
        return new RedirectResponse($this->generateUrl('admin_news'));
    }




    /**
     * KATEGORIE
     */

    /**
     * @Route("/news/categories", name="admin_news_categories")
     * @Template()
     */
    public function categoriesAction()
    {
        $em = $this->getDoctrine()->getManager();
        $categories = $em->getRepository('AppBundle:NewsCategoryLang')->createQueryBuilder('cl')
            ->select('cl, c, l')
            ->join('cl.category', 'c')
            ->join('cl.lang', 'l')
            ->groupBy('cl.category')
            ->getQuery()->getResult();

        return array(
            'categories' => $categories
        );
    }

    /**
     * @Route("/news/categories/langs/{id}", name="admin_news_categories_languages")
     * @Template()
     */
    public function categoryLanguagesAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $category = $em->getRepository('AppBundle:NewsCategory')->find($id);
        if(!$category)
            throw $this->createNotFoundException();

        $langs = $em->getRepository('AppBundle:NewsCategoryLang')->findBy(array(
            'category' => $category
        ));

        return array(
            'langs' => $langs,
            'category' => $category
        );
    }

    /**
     * @Route("/news/categories/langs/{id}/add", name="admin_news_categories_languages_add")
     */
    public function categoryLanguagesAddAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $category = $em->getRepository('AppBundle:NewsCategory')->find($id);
        if(!$category)
            throw $this->createNotFoundException();

        $newsCategoryLang = new NewsCategoryLang();
        $newsCategoryLang->setCategory($category);
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
                return new RedirectResponse($this->generateUrl('admin_news_categories_languages', array(
                    'id' => $id
                )));
            } catch(\Exception $e)
            {
                $this->addFlash('error', $this->get('translator')->trans('aktualnosci.kategorie.jezyk_nie_dodany', array(), 'admin'));
            }
        }

        return $this->render('AdminBundle:News:categories_add_edit.html.twig', array(
            'form' => $form->createView(),
            'category' => $category
        ));
    }

    /**
     * @Route("/news/categories/add", name="admin_news_categories_add")
     */
    public function addCategoryAction(Request $request)
    {
        $newsCategoryLang = new NewsCategoryLang();
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
                $category = new NewsCategory();
                $em->persist($category);

                $newsCategoryLang->setCategory($category);
                $em->persist($newsCategoryLang);

                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('aktualnosci.kategorie.dodana', array(), 'admin'));
                return new RedirectResponse($this->generateUrl('admin_news_categories'));
            } catch(\Exception $e)
            {
                $this->addFlash('error', $this->get('translator')->trans('aktualnosci.kategorie.nie_dodana', array(), 'admin'));
            }
        }

        return $this->render('AdminBundle:News:categories_add_edit.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/news/categories/langs/delete/{id}", name="admin_news_categories_lang_delete", requirements={
     *  "id": "\d+"
     * })
     */
    public function deleteLanguageCategoryAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $lang = $em->getRepository('AppBundle:NewsCategoryLang')->find($id);
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
     * @Route("/news/categories/delete/{id}", name="admin_news_categories_delete", requirements={
     *  "id": "\d+"
     * })
     */
    public function deleteCategoryAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $category = $em->getRepository('AppBundle:NewsCategory')->find($id);
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
        return new RedirectResponse($this->generateUrl('admin_news_categories'));
    }

    /**
     * @Route("/news/categories/edit/{id}", name="admin_news_categories_edit", requirements={
     *  "id": "\d+"
     * })
     */
    public function editCategoryAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $category = $em->getRepository('AppBundle:NewsCategory')->find($id);
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
                return new RedirectResponse($this->generateUrl('admin_news_categories'));
            }
            catch(\Exception $e)
            {
                $this->addFlash('error', $this->get('translator')->trans('aktualnosci.kategorie.nie_zmieniona', array(), 'admin'));
            }
        }

        return $this->render('AdminBundle:News:categories_add_edit.html.twig', array(
            'form' => $form->createView(),
            'category' => $category
        ));
    }
}
