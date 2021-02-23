<?php

namespace AdminBundle\Controller;

use AdminBundle\Form\InvoiceType;
use AdminBundle\Form\ReportType;
use AppBundle\Entity\Invoice;
use AppBundle\Entity\Report;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @Route("/admin/reports")
 */
class ReportsController extends Controller
{
    /**
     * @Route("/", name="admin_reports_index")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $query = $em->getRepository('AppBundle:Report')->createQueryBuilder('r');

        $paginator = $this->get('knp_paginator');
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
     * @Route("/add", name="admin_reports_add")
     */
    public function addAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $report = new Report();

        $form = $this->createForm(ReportType::class, $report);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $report->getFilename();
            if ($file instanceof UploadedFile) {
                $filename = $this->get('app.uploader')->upload($file, $this->getParameter('library_directory'));
                $report->setFilename($filename);
            }

            $image = $report->getImage();
            if ($image instanceof UploadedFile) {
                $image = $this->get('app.uploader')->upload($image, $this->getParameter('library_directory'));
                $report->setImage($image);
            }

            try {
                $em->persist($report);
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('raporty.dodany', [], 'admin'));
                return new RedirectResponse($this->generateUrl('admin_reports_index'));
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('raporty.nie_udalo_sie_dodac', [], 'admin'));
            }
        }

        return $this->render('AdminBundle:Reports:add_edit.html.twig', array(
            'form' => $form->createView()
        ));
    }

    /**
     * @Route("/users/{id}/delete/{user_id}", name="admin_reports_users_delete", requirements={
     *  "id": "\d+",
     *  "user_id": "\d+"
     * })
     */
    public function userDeleteAction($id, $user_id)
    {
        $em = $this->getDoctrine()->getManager();
        $userManager = $this->get('fos_user.user_manager');

        $report = $em->getRepository('AppBundle:Report')->find($id);
        $user = $userManager->findUserBy(array('id' => $user_id));

        if (!$report || !$user)
            throw $this->createNotFoundException();

        $report->removeUser($user);
        $em->persist($report);
        $em->flush();
        $this->addFlash('success', 'Dostęp do raportu został usunięty.');

        $lastRoute = $this->get('session')->get('last_route');
        if (!empty($lastRoute) && !empty($lastRoute['name'])) {
            return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
        } else {
            return new RedirectResponse(($this->generateUrl('homepage')));
        }
    }

    /**
     * @Route("/users/{id}", name="admin_reports_users", requirements={
     *  "id": "\d+"
     * })
     * @Template()
     */
    public function usersAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $userManager = $this->get('fos_user.user_manager');

        $report = $em->getRepository('AppBundle:Report')->createQueryBuilder('r')
            ->leftJoin('r.users', 'u')->addSelect('u')
            ->getQuery()->getSingleResult();
        if (!$report)
            throw $this->createNotFoundException();

        $form = $this->createFormBuilder()
            ->add('login', TextType::class, array(
                'label' => false,
                'attr' => array('placeholder' => 'Login użytkownika'),
                'constraints' => array(new NotBlank())
            ))->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $userManager->findUserBy(array('username' => $form->get('login')->getData()));
            if (!$user) {
                $this->addFlash('error', 'Nie znaleziono użytkownika o podanym loginie');
            } else {
                $report->addUser($user);
                $em->persist($report);
                $em->flush();
                $this->addFlash('success', 'Dostęp dla użytkownika został poprawnie dodany.');
            }
            return new RedirectResponse($this->generateUrl('admin_reports_users', array('id' => $report->getId())));
        }

        return array(
            'report' => $report,
            'form' => $form->createView()
        );
    }

    /**
     * @Route("/edit/{id}", name="admin_reports_edit", requirements={
     *  "id": "\d+"
     * })
     */
    public function editAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $report = $em->getRepository('AppBundle:Report')->find($id);
        if (!$report)
            throw $this->createNotFoundException();

        $originalFile = $report->getFilename();
        if ($originalFile && file_exists($this->getParameter('library_directory') . DIRECTORY_SEPARATOR . $originalFile))
            $report->setFilename(new File($this->getParameter('library_directory') . DIRECTORY_SEPARATOR . $originalFile));

        $originalImage = $report->getImage();
        if ($originalImage && file_exists($this->getParameter('library_directory') . DIRECTORY_SEPARATOR . $originalImage))
            $report->setImage(new File($this->getParameter('library_directory') . DIRECTORY_SEPARATOR . $originalImage));

        $form = $this->createForm(ReportType::class, $report);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $report->getFilename();
            if ($file instanceof UploadedFile) {
                $filename = $this->get('app.uploader')->upload($file, $this->getParameter('library_directory'));
                $report->setFilename($filename);
            } else {
                if ($originalFile && file_exists($this->getParameter('library_directory') . DIRECTORY_SEPARATOR . $originalFile))
                    $report->setFilename($originalFile);
            }
            $image = $report->getImage();
            if ($image instanceof UploadedFile) {
                $image = $this->get('app.uploader')->upload($image, $this->getParameter('library_directory'));
                $report->setImage($image);
            } else {
                if ($originalImage && file_exists($this->getParameter('library_directory') . DIRECTORY_SEPARATOR . $originalImage))
                    $report->setImage($originalImage);
            }

            try {
                $em->persist($report);
                $em->flush();
                $this->addFlash('success', 'Książka została zmieniona');
                return new RedirectResponse($this->generateUrl('admin_reports_index'));
            } catch (\Exception $e) {
                $this->addFlash('error', 'Nie udało się zmienić książki');
            }
        }

        return $this->render('AdminBundle:Reports:add_edit.html.twig', array(
            'form' => $form->createView(),
            'report' => $report
        ));
    }

    /**
     * @Route("/delete/{id}", name="admin_reports_delete", requirements={
     *  "id": "\d+"
     * })
     */
    public function deleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $report = $em->getRepository('AppBundle:Report')->find($id);
        if (!$report)
            throw $this->createNotFoundException();

        try {
            $em->remove($report);
            $em->flush();
            $this->addFlash('success', 'Książka została poprawnie usunięta');
        } catch (\Exception $e) {
            $this->addFlash('success', 'Książka nie mogła zostać usunięta');
        }

        $lastRoute = $this->get('session')->get('last_route');
        if (!empty($lastRoute) && !empty($lastRoute['name'])) {
            return new RedirectResponse($this->generateUrl($lastRoute['name'], $lastRoute['params']));
        } else {
            return new RedirectResponse(($this->generateUrl('homepage')));
        }
    }
}
