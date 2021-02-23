<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Newsletter;
use AppBundle\ReCaptcha\ReCaptcha;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class FooterController extends Controller
{

    /**
     * @Template()
     */
    public function getSocialAction($header = false)
    {
        $em = $this->getDoctrine()->getManager();

        $media = $em->getRepository('AppBundle:SocialMedia')->findBy(array(
            'enabled' => true
        ));

        return array(
            'media' => $media,
            'header' => $header
        );
    }

    /**
     * @Route("information/{page}", name="footer_information")
     */
    public function informationAction($page, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $page = $em->getRepository('AppBundle:StaticPage')->findBy(array(
            'url' => $page
        ));
        if(!$page)
            throw $this->createNotFoundException();

        $page = $page[0];
        $targetLang = $request->getLocale();
        foreach($page->getLangs() as $lang)
        {
            if($lang->getLang()->getShortcut() == $targetLang)
            {
                $content = $lang->getContent();
            }
        }

        $adsQb = $em->getRepository('AppBundle:Advert')->createQueryBuilder('a');
        $ads = $adsQb->select('l.content')
            ->join('a.langs', 'l')
            ->join('l.lang', 'la')
            ->where($adsQb->expr()->in('a.id', array(10)))
            ->andWhere('la.shortcut = :lang')
            ->setParameter(':lang', $request->getSession()->get('_locale'))
            ->getQuery()->getResult();

        return $this->render('AppBundle:Footer:Information.html.twig', array(
            'content' => isset($content) ? $content : null,
            'page' => $page,
            'ads' => $ads
        ));
    }

    /**
     * @Route("newsletter", name="newsletter")
     * @Template()
     */
    public function newsletterAction(Request $request)
    {
        $email = $request->request->get('email');
        if(!$email)
            throw $this->createNotFoundException();

        if(!filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            $this->addFlash('error', $this->get('translator')->trans('newsletter.bledny_email'));
            $lastRoute = $this->get('session')->get('last_route');
            if (!empty($lastRoute) && !empty($lastRoute['name'])) {
                return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
            } else {
                return new RedirectResponse(($this->generateUrl('homepage')));
            }
        }

        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->findUserByEmail($email);
        if($user)
        {
            $this->addFlash('error', $this->get('translator')->trans('newsletter.uzytkownik_istnieje'));
            $lastRoute = $this->get('session')->get('last_route');
            if (!empty($lastRoute) && !empty($lastRoute['name'])) {
                return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
            } else {
                return new RedirectResponse(($this->generateUrl('homepage')));
            }
        }

        $em = $this->getDoctrine()->getManager();
        $newsletter = $em->getRepository('AppBundle:Newsletter')->findBy(array(
            'email' => $email
        ));

        if($newsletter)
        {
            $this->addFlash('error', $this->get('translator')->trans('newsletter.email_jest_w_bazie'));
            $lastRoute = $this->get('session')->get('last_route');
            return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
        }

        $newsletter = new Newsletter();
        $newsletter->setEmail($email);
        $newsletter->setLocale($request->getLocale());
        $em->persist($newsletter);
        $em->flush();

        return array();
    }

    /**
     * @Route("/contact", name="contact")
     * @Template()
     */
    public function contactAction(Request $request)
    {
        $recaptcha = new ReCaptcha($this->getParameter('google_recaptcha_secret_key'));

        $form = $this->createFormBuilder()
            ->add('email', EmailType::class, array(
                'label' => 'kontakt.adres_email',
                'constraints' => array(new Email())
            ))->add('title', TextType::class, array(
                'label' => 'main.tytul',
                'constraints' => array(new NotBlank())
            ))->add('content', TextareaType::class, array(
                'label' => 'kontakt.wiadomosc',
                'constraints' => array(new NotBlank())
            ))->add('submit', SubmitType::class, array(
                'attr' => array('class' => 'btn btn-primary'),
                'label' => 'main.wyslij'
            ))->getForm();
        $form->handleRequest($request);
        $resp = $recaptcha->verify($request->request->get('g-recaptcha-response'), $request->getClientIp());
        if($form->isSubmitted() && $form->isValid() && $resp->isSuccess())
        {
            try {
                $message = \Swift_Message::newInstance()
                    ->setSubject($form->get('title')->getData())
                    ->setFrom($form->get('email')->getData())
                    ->setTo('contact@artbidy.com')
                    ->setBody($form->get('content')->getData(), 'text/html');
                $this->get('mailer')->send($message);
                $this->addFlash('success', $this->get('translator')->trans('kontakt.udalo_sie'));
                return new RedirectResponse($this->generateUrl('contact'));
            } catch(\Exception $e)
            {
                //$message = "The reCAPTCHA wasn't entered correctly. Go back and try it again." . "(reCAPTCHA said: " . $resp->error . ")";
                $this->addFlash('error', $this->get('translator')->trans('kontakt.nie_udalo_sie'));
            }
        }

        return array(
            'form' => $form->createView(),
            'google_recaptcha_api_key' => $this->getParameter('google_recaptcha_api_key')
        );
    }
}
