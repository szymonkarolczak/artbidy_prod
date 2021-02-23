<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Form\UserType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Cocur\Slugify\Slugify;
use Psr\Log\LoggerInterface;


/**
 * @Route("/users")
 */
class UsersController extends Controller
{
    /**
     * @Route("/add", name="users_add")
     * @Template()
     */
    public function addAction(Request $request)
    {
        /** @var LoggerInterface logger */
        $logger = $this->get('logger');
        $logger->info('Start Controller: '.__CLASS__. ' Action: '.__FUNCTION__);

        if(!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_REDAKTOR') && !$this->isGranted('ROLE_GALERIA') && !$this->isGranted('ROLE_DOM_AUKCYJNY') && !$this->isGranted('ROLE_MUZEUM'))
            throw $this->createAccessDeniedException ();
        
        $userManager = $this->get('fos_user.user_manager');
        $user = $userManager->createUser();
        $user->setImage(null);

        $em = $this->getDoctrine()->getManager();

        $user->setCard([
            [
                'lang' => 'pl',
                'content' => ''
            ],
            [
                'lang' => 'en',
                'content' => ''
            ]
        ]);

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $user->setEnabled(1);
            $user->setCreator($this->getUser());
            $user->addRole('ROLE_ARTYSTA');
            $user->setUsername($this->get('slugify')->slugify($user->getFullname()).'_'. uniqid());
            $user->setPassword(substr(str_shuffle('abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ123456789123456789!@#$%^&*!@#$%^&*'), 0, 10));
            $user->setEmail('no-reply'. uniqid().'@finearts.com');
            
            $file = $user->getImage();
            if($file instanceof UploadedFile)
            {
                $fileName = $this->get('app.uploader')->upload($file, $this->getParameter('user_files_directory'));
                $user->setImage($fileName);
            }
            $slugify = new Slugify();
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
            try {
                $userManager->updateUser($user);
                $this->addFlash('success', $this->get('translator')->trans('user.stworzono'));
                return new \Symfony\Component\HttpFoundation\RedirectResponse($this->generateUrl('profile', array(
                    'id' => $user->getId(),
                    'prefix' => 'artists',
                    'slug' => $this->get('slugify')->slugify($user->getFullname())
                )));
            } catch(\Exception $e)
            {
                $this->addFlash('error', $this->get('translator')->trans('user.nie_stworzono'));
            }
        }
        
        return array(
            'form' => $form->createView()
        );
    }
}
