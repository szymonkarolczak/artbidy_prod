<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace UserBundle\Controller;

use AppBundle\Entity\Newsletter;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\UserBundle\Controller\RegistrationController as BaseClass;
use Cocur\Slugify\Slugify;
use UserBundle\Entity\User;

/**
 * Controller managing the registration.
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 * @author Christophe Coevoet <stof@notk.org>
 */
class RegistrationController extends BaseClass
{
    /**
     * @param Request $request
     *
     * @return Response
     */
    public function registerAction(Request $request)
    {
        /** @var $formFactory FactoryInterface */
        $formFactory = $this->get('fos_user.registration.form.factory');
        /** @var $userManager UserManagerInterface */
        $userManager = $this->get('fos_user.user_manager');
        /** @var $dispatcher EventDispatcherInterface */
        $dispatcher = $this->get('event_dispatcher');
        /** @var User $user */
        $user = $userManager->createUser();
        $user->setEnabled(true);

        $event = new GetResponseUserEvent($user, $request);
        $dispatcher->dispatch(FOSUserEvents::REGISTRATION_INITIALIZE, $event);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $form = $formFactory->createForm();
        $form->setData($user);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()
//                && $reCaptcha = $this->gRecaptchaValidate($request->request->get('g-recaptcha-response'))
            ) {
                $event = new FormEvent($form, $request);
                $dispatcher->dispatch(FOSUserEvents::REGISTRATION_SUCCESS, $event);
                /* Start part of slug generation for profile */
                $slugify = new Slugify();
                $slug = '';
                if (!empty($user->getFullname())) {
                    $slug = $slugify->slugify($user->getFullname());
                } elseif (!empty($user->getUsername())) {
                    $slug = $slugify->slugify($user->getUsername());
                }
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
                do {
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
                /* Finish part of slug generation for profile */

                $userManager->updateUser($user);

                if ($user->getNewsletter()) {
                    $locale = $user->getCountry() == 'Polska' || $user->getCountry() == 'Poland' ? 'pl' : 'en';
                    $newsletter = new Newsletter();
                    $newsletter->setEmail($user->getEmail());
                    $newsletter->setLocale($locale);

                    $em = $this->getDoctrine()->getManager();
                    $em->persist($newsletter);
                    $em->flush();
                }

                if (null === $response = $event->getResponse()) {

                    $url = $this->getParameter('fos_user.registration.confirmation.enabled')
                        ? $this->generateUrl('fos_user_registration_confirmed')
                        : $this->generateUrl('fos_user_profile_show');

                    $response = new RedirectResponse($url);
                }

                $dispatcher->dispatch(FOSUserEvents::REGISTRATION_COMPLETED, new FilterUserResponseEvent($user, $request, $response));

                return $response;
            }

//            if(!$reCaptcha)
//            {
//                $this->addFlash('error', 'Udowodnij, że nie jesteś robotem.');
//            }

            $event = new FormEvent($form, $request);
            $dispatcher->dispatch(FOSUserEvents::REGISTRATION_FAILURE, $event);

            if (null !== $response = $event->getResponse()) {
                return $response;
            }
        }

        $em = $this->getDoctrine()->getManager();
        $adsQb = $em->getRepository('AppBundle:Advert')->createQueryBuilder('a');
        $ads = $adsQb->select('l.content')
            ->join('a.langs', 'l')
            ->join('l.lang', 'la')
            ->where($adsQb->expr()->in('a.id', array(12)))
            ->andWhere('la.shortcut = :lang')
            ->setParameter(':lang', $request->getSession()->get('_locale'))
            ->getQuery()->getResult();

        return $this->render('FOSUserBundle:Registration:register.html.twig', array(
            'form' => $form->createView(),
            'artist_price' => $this->getParameter('artysta_3msc'),
            'gallery_price' => $this->getParameter('galeria_3msc'),
            'auction_house_price' => $this->getParameter('dom_aukcyjny_3msc'),
            'muzeum_price' => $this->getParameter('muzeum_3msc'),
            'ads' => $ads,
            'csrf_token' => false
        ));
    }

    private function gRecaptchaValidate($captcha)
    {
        $url = "https://www.google.com/recaptcha/api/siteverify";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            "secret" => "6LfK1xsUAAAAAIt_GSA5OT_avgzgP_lFN4kvLjAD", "response" => $captcha
        ));
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response);

        return $data->success;
    }
}
