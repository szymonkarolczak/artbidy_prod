<?php

namespace AdminBundle\Controller;

use AdminBundle\Form\NewsletterType;
use AdminBundle\Form\UserType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use AdminBundle\Form\WorkType;

/**
 * @Route("/admin/newsletter")
 */
class NewsletterController extends Controller
{

    const CUSTOM = 'custom';

    /**
     * @Route("/", name="admin_newsletter")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $emails = $em->getRepository('AppBundle:Newsletter')->createQueryBuilder('n');

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $emails,
            $request->query->getInt('page', 1),
            25
        );

        return array(
            'pagination' => $pagination
        );
    }

    /**
     * @Route("/show", name="admin_newsletter_show")
     * @Template()
     */
    public function showAction(Request $request)
    {
        $content = $request->request->get('content');
        $title = $request->request->get('title');
        $typ = $request->request->get('typ');
        if ($typ === self::CUSTOM) {
            $newsletter = $content;
            $subject = $title;
        } else {
            $subject = $this->get('translator')->trans('mail.newsletter');
            $newsletter = $this->getEmailText($request->getLocale());
        }
        $form = $this->createForm(NewsletterType::class);
        $form->handleRequest($request);
        if ($email = $request->request->get('email')) {
            $message = \Swift_Message::newInstance()
                ->setSubject($subject)
                ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
                ->setTo($email)
                ->setBody($newsletter, 'text/html');
            $this->get('mailer')->send($message);
            $this->addFlash('success', $this->get('translator')->trans('mail.wyslany'));
        }
        return array(
            'newsletter' => $newsletter,
            'form' => $form->createView()
        );
    }

    /**
     * @Route("/break", name="newsletter_break")
     */
    public function breakAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $lastRoute = $this->get('session')->get('last_route');
        $cron = $em->getRepository('AppBundle:Cron')->find(1);
        $cron->setActive(0);
        $em->persist($cron);
        $em->flush();
        $this->addFlash('success', 'Wysyłanie newslettera zostało przerwane.');
        if (!empty($lastRoute) && !empty($lastRoute['name'])) {
            return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
        } else {
            return new RedirectResponse(($this->generateUrl('homepage')));
        }
    }


    /**
     * @Route("/send", name="newsletter_send_now")
     */
    public function sendNowAction(Request $request)
    {

        $title = $request->request->get('title');
        $titleEn = $request->request->get('titleEn');

        $content = $request->request->get('content');
        $contentEn = $request->request->get('contentEn');

        $typ = $request->request->get('typ');

        $em = $this->getDoctrine()->getManager();
        $lastRoute = $this->get('session')->get('last_route');

        $cron = $em->getRepository('AppBundle:Cron')->find(1);
        if($cron->getActive())
        {
            $this->addFlash('error', 'Wysyłanie newslettera jest aktualnie włączone.');
            return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
        }

        $countMails = $em->getRepository('AppBundle:Newsletter')->createQueryBuilder('n')
            ->select('COUNT(n.id) AS ile')
            ->getQuery()->getSingleScalarResult();
//        $countMails = $em->getRepository('AppBundle:NewsletterTest')->createQueryBuilder('n')
//            ->select('COUNT(n.id) AS ile')
//            ->getQuery()->getSingleScalarResult();

        if ($typ === 'generated' || !empty($content) || !empty($contentEn)) {
            $cron->setActive(true);
            $cron->setParams(array(
                'start' => 0,
                'max' => $countMails
            ));
            $em->persist($cron);
        } else {
            if (!empty($lastRoute) && !empty($lastRoute['name'])) {
                return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
            } else {
                return new RedirectResponse(($this->generateUrl('homepage')));
            }
        }

        $settings = $em->getRepository('AppBundle:Settings')->createQueryBuilder('s')
            ->where('s.name = :name')
            ->setParameter(':name', 'newsletterHtml-pl')
            ->getQuery()->getSingleResult();

        if ($typ === 'generated') {
            $settings->setValue($this->getEmailText('pl'));
        } else {
            if (!empty($content)) {
                $settings->setValue($content);
            } else {
                $settings->setValue($contentEn);
            }
        }
        $em->persist($settings);

        $settings = $em->getRepository('AppBundle:Settings')->createQueryBuilder('s')
            ->where('s.name = :name')
            ->setParameter(':name', 'newsletterTitle-pl')
            ->getQuery()->getSingleResult();

        if ($typ === 'generated') {
            $settings->setValue('Artbidy');
        } else {
            if (!empty($title)) {
                $settings->setValue($title);
            } else {
                $settings->setValue($titleEn);
            }
        }
        $em->persist($settings);

        $settings = $em->getRepository('AppBundle:Settings')->createQueryBuilder('s')
            ->where('s.name = :name')
            ->setParameter(':name', 'newsletterHtml-en')
            ->getQuery()->getSingleResult();

        if ($typ === 'generated') {
            $settings->setValue($this->getEmailText('en'));
        } else {
            if (!empty($contentEn)) {
                $settings->setValue($contentEn);
            } else {
                $settings->setValue($content);
            }
        }

        $settings = $em->getRepository('AppBundle:Settings')->createQueryBuilder('s')
            ->where('s.name = :name')
            ->setParameter(':name', 'newsletterTitle-en')
            ->getQuery()->getSingleResult();

        if ($typ === 'generated') {
            $settings->setValue('Artbidy');
        } else {
            if (!empty($titleEn)) {
                $settings->setValue($titleEn);
            } else {
                $settings->setValue($title);
            }
        }

        $em->persist($settings);
        $em->flush();

        //$this->sendNewsletter($request, $countMails);

        $this->addFlash('success', 'Wysyłanie newslettera zostało uruchomione. Pierwsze maile zostaną wysłane za kilka minut.');
        if (!empty($lastRoute) && !empty($lastRoute['name'])) {
            return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
        } else {
            return new RedirectResponse(($this->generateUrl('homepage')));
        }
    }

    public function sendNewsletter($request, $countMails)
    {

        $em = $this->getDoctrine()->getManager();
        $getEmails = $em->getRepository('AppBundle:Newsletter')->createQueryBuilder('n')
            ->select('n.email')
            ->getQuery()->getResult();

        $newsletter = $this->getEmailText($request->getLocale());

        foreach ($getEmails as $key => $value) {
            foreach ($value as $k => $v) {
                $message = \Swift_Message::newInstance()
                    ->setSubject($this->get('translator')->trans('mail.newsletter'))
                    ->setFrom(array($this->getParameter('mail_address_from') => 'Artbidy'))
                    ->setTo($v)
                    ->setBody($newsletter, 'text/html');
                $this->get('mailer')->send($message);
            }

        }

    }


    private function getEmailText($locale)
    {
        $em = $this->getDoctrine()->getManager();

        $latestWorks = $em->getRepository('AppBundle:Work')->createQueryBuilder('w')
            ->where('w.approved = :approved')
            ->andWhere('w.display = :approved')
            ->setParameter(':approved', true)
            ->orderBy('w.id', 'DESC')
            ->setMaxResults(8)->getQuery()->getResult();

        $latestAuctions = $em->getRepository('AppBundle:Auction')->createQueryBuilder('w')
            ->join('w.langs', 'wl')->addSelect('wl.title')
            ->join('wl.lang', 'l')
            ->where('l.shortcut = :shortcut')
            ->setParameter(':shortcut', $locale)
            ->orderBy('w.id', 'DESC')
            ->setMaxResults(8)->getQuery()->getResult();

        $latestEvents = $em->getRepository('AppBundle:Event')->createQueryBuilder('w')
            ->join('w.langs', 'wl')->addSelect('wl.title')
            ->join('wl.lang', 'l')
            ->where('l.shortcut = :shortcut')
            ->setParameter(':shortcut', $locale)
            ->orderBy('w.id', 'DESC')
            ->setMaxResults(8)->getQuery()->getResult();

        $latestNews = $em->getRepository('AppBundle:News')->createQueryBuilder('n')
            ->join('n.langs', 'lg')->addSelect('lg.title')
            ->join('lg.lang', 'l')
            ->where('l.shortcut = :shortcut')
            ->setParameter(':shortcut', $locale)
            ->orderBy('n.id', 'DESC')
            ->setMaxResults(8)->getQuery()->getResult();

        return $this->renderView('AppBundle:Emails:newsletter.html.twig', array(
            'works' => $latestWorks,
            'auctions' => $latestAuctions,
            'events' => $latestEvents,
            'newses' => $latestNews,
            'user' => $this->getUser(),
            'locale' => $locale
        ));
    }

}
