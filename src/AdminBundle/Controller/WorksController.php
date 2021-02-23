<?php

namespace AdminBundle\Controller;

use AdminBundle\Form\WorkConfigType;
use AppBundle\Entity\WorkConfig;
use AppBundle\Entity\Work;
use AppBundle\Repository\WorkRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Cocur\Slugify\Slugify;
use AdminBundle\Form\WorkType;
use Symfony\Component\Yaml\Yaml;

/**
 * @Route("/admin/works")
 */
class WorksController extends Controller
{
    /**
     * @Route("/", name="admin_works")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $page = $request->query->getInt('page', 1);
        $data = $this->_getWorks($page);
        return array(
            'pagination' => $data[0],
            'filters' => $this->renderView('AdminBundle::filters.html.twig', array(
                'filters' => $data[1]->getFilters()
            ))
        );
    }

    /**
     * @Route("/waiting", name="admin_works_waiting")
     * @Template()
     */
    public function waitingAction(Request $request)
    {
        $page = $request->query->getInt('page', 1);
        $data = $this->_getWorks($page, false);
        return array(
            'pagination' => $data[0],
            'filters' => $this->renderView('AdminBundle::filters.html.twig', array(
                'filters' => $data[1]->getFilters()
            ))
        );
    }

    private function _getWorks($page, $approved = true)
    {
        $em = $this->getDoctrine()->getManager();

        $filter = $this->get('admin.filter');
        $filter->setConfiguration(array(
            'title' => array(
                'name' => 'Tytuł',
                'type' => 'string'
            ),
            'artist' => array(
                'name' => 'Artysta',
                'type' => 'string'
            )
        ));
        $query = $filter->parseQuery($em->getRepository('AppBundle:Work')->createQueryBuilder('w'), 'w');

        $query->andWhere('w.approved = :approved')
            ->join('w.author', 'u')->addSelect('u.username')
            ->leftJoin('w.approved_by', 'u2')->addSelect('u2.username AS approved_username')
            ->orderBy('w.id', 'DESC')
            ->setParameter(':approved', $approved);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($query, $page, 10);
        return [$pagination, $filter];
    }

    /**
     * @Route("/generate-slugs/", name="admin_work_generate_slugs")
     * @Template()
     */
    public function generateSlugsAction(Request $request)
    {
        /** @var LoggerInterface logger */
        $logger = $this->get('logger');
        $logger->info('Start Controller: ' . __CLASS__ . ' Action: ' . __FUNCTION__);
        /** @var WorkRepository $work_repository */
        $work_repository = $this->getDoctrine()->getRepository('AppBundle:Work');
        $works = $work_repository->findAll();
        $slugify = new Slugify();
        foreach ($works as $work) {
            $slug = $work->getSlug();
            if (empty($slug)) {
                if (!empty($work->getTitle())) {
                    $slug = $slugify->slugify($work->getTitle());
                } else {
                    $slug = $slugify->slugify(uniqid('work', true));
                }
                $work->setSlug($work_repository->checkSlug($slug, $work->getId()));
                $logger->info('Slug for object #' . $work->getId() . ' is ' . $work->getSlug());
                $work_repository->update($work);
            }
        }
        $this->addFlash('success', $this->get('translator')->trans('created all slug', [], 'admin'));
        return new RedirectResponse($this->generateUrl('admin_works'));
    }

    /**
     * @Route("/edit/{id}", name="admin_works_edit", requirements={
     *  "id": "\d+"
     * })
     * @Template()
     */
    public function editAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var WorkRepository $work_repository */
        $work_repository = $em->getRepository('AppBundle:Work');
        /** @var Work $work */
        $work = $work_repository->find($id);
        if (!$work)
            throw $this->createNotFoundException();

        $image = $work->getImage();
        if ($work->getImage() && file_exists($this->getParameter('work_files_directory') . '/' . $work->getImage()))
            $work->setImage(
                new File($this->getParameter('work_files_directory') . '/' . $work->getImage())
            );
        else $work->setImage(null);

        $types = $em->getRepository('AppBundle:WorkConfig')->createQueryBuilder('wc')
            ->select('wc.value')
            ->where('wc.lang = :lang')
            ->andWhere('wc.type = :type')
            ->setParameter(':type', 'typ')
            ->setParameter(':lang', $em->getRepository('AppBundle:Language')->findOneBy(array(
                'shortcut' => $request->getLocale()
            )))
            ->getQuery()->getScalarResult();

        $typesAr = array();
        foreach ($types as $typ) {
            $typesAr[] = $typ['value'];
        }

        $form = $this->createForm(WorkType::class, $work, [
            'types' => $typesAr
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $work->getImage();
            if ($file instanceof UploadedFile) {
                $fileName = $this->get('app.uploader')->upload($file, $this->getParameter('work_files_directory'));
                $work->setImage($fileName);
            } else {
                $work->setImage($image);
            }

            $slug = $work->getSlug();
            $slugify = new Slugify();
            if (empty($slug)) {
                if (!empty($work->getTitle())) {
                    $slug = $slugify->slugify($work->getTitle());
                } else {
                    $slug = $slugify->slugify(uniqid('work', true));
                }
            }
            $work->setSlug($work_repository->checkSlug($slug, $work->getId()));
            try {
                $em->persist($work);
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('dziela.zmienione', [], 'admin'));
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('dziela.nie_zmienione', [], 'admin'));
            }

            return new RedirectResponse($this->generateUrl('admin_works_edit', array(
                'id' => $id
            )));
        }

        return array(
            'work' => $work,
            'form' => $form->createView()
        );
    }

    /**
     * @Route("/waiting/accept/{id}", name="admin_works_waiting_accept", requirements={
     *  "id": "\d+"
     * })
     */
    public function acceptAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $work = $em->getRepository('AppBundle:Work')->find($id);
        if (!$work)
            throw $this->createNotFoundException();

        if ($work->getApproved()) {
            $this->addFlash('success', $this->get('translator')->trans('dziela.dzielo_juz_zaakceptowane', [], 'admin'));
            return new RedirectResponse($this->generateUrl('admin_works_waiting'));
        }

        $work->setApproved(true);
        $work->setApprovedDate(new \DateTime());
        $work->setApprovedBy($this->getUser());
        $em->persist($work);
        $em->flush();
        $this->addFlash('success', $this->get('translator')->trans('dziela.dzielo_zaakceptowane', [], 'admin'));

        $author = $work->getAuthor();
        $message = \Swift_Message::newInstance()
            ->setSubject($this->get('translator')->trans('mail.temat_dzielo_zaakceptowane', [], 'admin'))
            ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
            ->setTo($author->getEmail())
            ->setBody(
                $this->renderView('AdminBundle:Emails:work_accepted.html.twig', array(
                    'work' => $work,
                    'author' => $author
                )),
                'text/html'
            );
        $this->get('mailer')->send($message);

        $usersObserving = $em->getRepository('AppBundle:ProfileObserve')->createQueryBuilder('po')
            ->join('po.user', 'u')->addSelect('u')
            ->where('po.targetUser = :user')
            ->setParameter(':user', $author)
            ->getQuery()->getResult();
        foreach ($usersObserving as $obUser) {
            $message = \Swift_Message::newInstance()
                ->setSubject($this->get('translator')->trans('notifications.user_dzielo'))
                ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
                ->setTo($obUser->getUser()->getEmail())
                ->setBody(
                    $this->renderView(
                        '@App/Emails/notification_user_work.html.twig',
                        array(
                            'work' => $work,
                            'user' => $obUser->getUser(),
                            'target' => $author,
                            'lang' => $obUser->getUser()->getCountry() == 'Polska' || $obUser->getUser()->getCountry() == 'Poland' ? 'pl' : 'en'
                        )
                    ),
                    'text/html'
                );
            $this->get('mailer')->send($message);
        }

        return new RedirectResponse($this->generateUrl('admin_works_waiting'));
    }

    /**
     * @Route("/delete/{id}", name="admin_works_delete", requirements={
     *  "id": "\d+"
     * })
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $work = $em->getRepository('AppBundle:Work')->find($id);
        if (!$work)
            throw $this->createNotFoundException();

//        $author = $work->getAuthor();
//        $message = \Swift_Message::newInstance()
//            ->setSubject($this->get('translator')->trans('mail.temat_dzielo_usuniete', [], 'admin'))
//            ->setFrom(array($this->getParameter('mail_address_from') => 'Fineart`s'))
//            ->setTo($author->getEmail())
//            ->setBody(
//                $this->renderView('AdminBundle:Emails:work_deleted.html.twig', array(
//                    'work' => $work,
//                    'author' => $author
//                )),
//                'text/html'
//            );
//        $this->get('mailer')->send($message);

        $em->remove($work);
        $em->flush();
        $this->addFlash('success', $this->get('translator')->trans('dziela.dzielo_usuniete', [], 'admin'));

        return new RedirectResponse($this->generateUrl('admin_works_waiting'));
    }

    /**
     * @Route("/configuration", name="admin_works_configuration")
     * @Template()
     */
    public function configurationAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $newConfig = new WorkConfig();
        $form = $this->createForm(WorkConfigType::class, $newConfig);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($newConfig);
            $em->flush();
        }

        $filter = $this->get('admin.filter');
        $filter->setConfiguration(array(
            'value' => array(
                'name' => 'Wartość',
                'type' => 'string'
            ),
            'type' => array(
                'name' => 'Typ',
                'type' => 'select',
                'choices' => array('styl', 'technika', 'typ')
            )
        ));
        $query = $filter->parseQuery($em->getRepository('AppBundle:WorkConfig')->createQueryBuilder('wc'), 'wc');
        $query->andWhere('wc.parent IS NULL');
        $query->orderBy('wc.id', 'DESC');
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            25
        );

        $requestFilter = $request->get('_filter');
        if (isset($requestFilter['value']) && !empty($requestFilter['value'])) {
        }

        return array(
            'pagination' => $pagination,
            'form' => $form->createView(),
            'filters' => $this->renderView('AdminBundle::filters.html.twig', array(
                'filters' => $filter->getFilters()
            ))
        );
    }

    /**
     * @Route("/configuration/langs/{id}", name="admin_works_configuration_languages")
     * @Template()
     */
    public function configurationLanguagesAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $config = $em->getRepository('AppBundle:WorkConfig')->find($id);
        if (!$config)
            throw $this->createNotFoundException();

        $newConfig = new WorkConfig();
        $newConfig->setParent($config);
        $newConfig->setType($config->getType());
        $form = $this->createForm(WorkConfigType::class, $newConfig, array(
            'langs' => true
        ));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($newConfig);
            $em->flush();
        }

        $langs = $em->getRepository('AppBundle:WorkConfig')->createQueryBuilder('wc')
            ->where('wc.parent = :config')
            ->setParameter(':config', $config);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $langs,
            $request->query->getInt('page', 1),
            25
        );

        return array(
            'config' => $config,
            'form' => $form->createView(),
            'pagination' => $pagination
        );
    }

    /**
     * @Route("/configuration/{id}", name="admin_works_configuration_delete", requirements={
     *  "id": "\d+"
     * })
     * @Template()
     */
    public function configurationDeleteAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $config = $em->getRepository('AppBundle:WorkConfig')->find($id);
        if (!$config)
            throw $this->createNotFoundException();

        $em->remove($config);
        $em->flush();
        $this->addFlash('success', 'Pole zostało poprawnie usunięte.');
        return new RedirectResponse($this->generateUrl('admin_works_configuration'));
    }

    /**
     * @Route("/configuration/generate", name="admin_works_configuration_generate")
     */
    public function generateFilesAction()
    {
        $em = $this->getDoctrine()->getManager();
        $translationPath = $this->get('kernel')->getRootDir() . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'translations' . DIRECTORY_SEPARATOR;

        $config = $em->getRepository('AppBundle:WorkConfig')->createQueryBuilder('wc')
            ->join('wc.lang', 'l')->addSelect('l')
            ->orderBy('l.shortcut', 'DESC')
            ->getQuery()->getResult();
        $yamlAr = [];
        foreach ($config as $row) {
            if ($row->getLang()->getShortcut() == 'pl') {
                $yamlAr[$row->getValue()] = $row->getValue();
            } else {
                $yamlAr[$row->getValue()] = $row->getParent()->getValue();
            }
        }

        $res1 = file_put_contents($translationPath . 'work.pl.yml', Yaml::dump($yamlAr));
        $res2 = file_put_contents($translationPath . 'work.en.yml', Yaml::dump(array_flip($yamlAr)));
        if ($res1 !== FALSE && $res2 !== FALSE) {
            $this->addFlash('success', 'Pliki zostały poprawnie wygenerowane.');
        } else {
            $this->addFlash('error', 'Nie udało się wygenerować plików.');
        }
        return new RedirectResponse($this->generateUrl('admin_works_configuration'));
    }

    /**
     * @Route("/results", name="admin_works_results")
     * @Template()
     */
    public function resultsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $auctionWorks = $em->getRepository('AppBundle:Work')->createQueryBuilder('w')
            ->join('w.bids', 'b')->addSelect('b')
            ->join('w.author', 'aut')->addSelect('aut')
            ->join('w.currency', 'c')->addSelect('c')
            ->join('b.author', 'u')->addSelect('u');


        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $auctionWorks,
            $request->query->getInt('page', 1),
            25
        );
//        echo "test2";

        return array(
            'pagination' => $pagination
        );
    }

    /**
     * @Route("/results/markPaid/{id}", name="admin_works_results_paid")
     */
    public function markAsPaidAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $work = $em->getRepository('AppBundle:Work')->find($id);
        if (!$work)
            throw $this->createNotFoundException();

        $bid = $work->getBids()->last();
        $bid->setProvisionPaid(true);
        $em->persist($bid);
        $em->flush();

        $this->addFlash('success', 'Zapisano płatność prowizji dla sprzedaży dzieła.');

        $message = \Swift_Message::newInstance()
            ->setSubject($this->get('translator')->trans('mail.auction.prowizja_temat'))
            ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
            ->setTo($bid->getAuthor()->getEmail())
            ->setBody($this->renderView('@App/Emails/work_provision_buyer.html.twig', array(
                'work' => $work,
                'bid' => $bid
            )), 'text/html');

        $this->get('mailer')->send($message);

        $message = \Swift_Message::newInstance()
            ->setSubject($this->get('translator')->trans('mail.auction.prowizja_temat_seller', array(
                '%work%' => $work->getTitle()
            )))
            ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
            ->setTo($work->getAuthor()->getEmail())
            ->setBody($this->renderView('@App/Emails/work_provision_seller.html.twig', array(
                'work' => $work,
                'bid' => $bid
            )), 'text/html');

        $this->get('mailer')->send($message);

        return new RedirectResponse(($this->generateUrl('admin_works_results')));
    }


    /**
     * @Route("/results/remindePaid/{id}", name="admin_works_results_reminde_paid")
     */
    public function remindePaidAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $work = $em->getRepository('AppBundle:Work')->find($id);
        if (!$work)
            throw $this->createNotFoundException();

        $bid = $work->getBids()->last();

        $this->addFlash('success', 'Wysłano!');

        $message = \Swift_Message::newInstance()
            ->setSubject($this->get('translator')->trans('mail.pay.remember', array(
                '%work%' => $work->getTitle()
            )))
            ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
            ->setTo($bid->getAuthor()->getEmail())
            ->setBody($this->renderView('@App/Emails/payment_works_reminders.html.twig', array(
                'work' => $work,
                'bid' => $bid,
                'lang' => $request->getLocale(),
            )), 'text/html');
        $this->get('mailer')->send($message);

        return new RedirectResponse($this->generateUrl('admin_works_results'));
    }

    /**
     * @Route("/results/cancelPaid/{id}", name="admin_works_results_cancel")
     */
    public function cancelPaidAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $work = $em->getRepository('AppBundle:Work')->find($id);
        if (!$work)
            throw $this->createNotFoundException();

        $bid = $work->getBids()->last();
        $em->remove($bid);
        $em->flush();

        return new RedirectResponse($this->generateUrl('admin_works_results'));
    }
}
