<?php

namespace UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;

use UserBundle\Form\CardType;
use UserBundle\Form\SocialMediaType;

/**
 * @Route("/profile/card")
 */
class CardController extends Controller
{
    
    /**
     * @Route("/general", name="profile_card_general")
     * @Template()
     */
    public function generalAction(Request $request)
    {
        $user = $this->getUser();
        if (!is_object($user)) {
            throw $this->createAccessDeniedException();
        }
        if(!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_REDAKTOR') && !$this->isGranted('ROLE_MUZEUM') && !$this->isGranted('ROLE_ARTYSTA') && !$this->isGranted('ROLE_GALERIA') && !$this->isGranted('ROLE_DOM_AUKCYJNY'))
            throw $this->createAccessDeniedException ();
        
        $image = $user->getImage();
        if($user->getImage() && file_exists($this->getParameter('user_files_directory').'/'.$user->getImage()))
            $user->setImage(new File($this->getParameter('user_files_directory').'/'.$user->getImage()));
        else $user->setImage(null);

        $em = $this->getDoctrine()->getManager();
        $langs = $em->getRepository('AppBundle:Language')->findAll();
        $cards = array();
        foreach($langs as $lang)
            $cards[] = array(
                'lang' => $lang->getShortcut(),
                'content' => ''
            );
        $result = (array)$user->getCard() + $cards;
        $user->setCard($result);

        $form = $this->createForm(CardType::class, $user, array(
            'roles' => $this->getUser()->getRoles()
        ));
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $userManager = $this->get('fos_user.user_manager');
            
            $file = $user->getImage();
            if($file instanceof UploadedFile)
            {
                $fileName = $this->get('app.uploader')->upload($file, $this->getParameter('user_files_directory'));
                $user->setImage($fileName);
            } else {
                $user->setImage($image);
            }
            
            $userManager->updateUser($user);
            //$success = $this->get('translator')->trans('main.dane_zmienione');
            return new RedirectResponse($this->generateUrl('profile_card_general'));
        }
        
        return array(
            'form' => $form->createView(),
            'success' => isset($success) ? $success : false
        );
    }
    
    /**
     * @Route("/socialmedia", name="profile_card_socialmedia")
     * @Template()
     */
    public function socialMediaAction(Request $request)
    {
        $user = $this->getUser();
        if (!is_object($user)) {
            throw $this->createAccessDeniedException();
        }

        if(!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_REDAKTOR') && !$this->isGranted('ROLE_MUZEUM') && !$this->isGranted('ROLE_ARTYSTA') && !$this->isGranted('ROLE_GALERIA') && !$this->isGranted('ROLE_DOM_AUKCYJNY'))
            throw $this->createAccessDeniedException();

        $em = $this->getDoctrine()->getManager();

        $form = $this->createFormBuilder($user)
            ->add('socialMedia', CollectionType::class, array(
                'label' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'entry_type' => SocialMediaType::class,
                'entry_options' => array(
                    'label' => false
                )
            ))->add('submit', SubmitType::class, array(
                'label' => 'main.wyslij',
                'attr' => array('class' => 'btn btn-success')
            ))->getForm();

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $em->persist($user);
            $em->flush();
        }
        
        return array(
            'form' => $form->createView()
        );
    }
    
}
