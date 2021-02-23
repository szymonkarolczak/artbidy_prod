<?php

namespace AdminBundle\Form;

use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class NewsletterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, array(
            'label' => 'Tytuł',
            'attr'  => array('style' => 'width: 100%;'),
            'required' => true,
        ))
            ->add('content', CKEditorType::class, array(
                'label' => 'Treść (pl)',
                'config' => array(
                    //'filebrowserBrowseUrl' => '/admin/kcfinder/browse.php?opener=ckeditor&type=files',
                    'filebrowserImageBrowseUrl' => '/admin/kcfinder/browse.php?opener=ckeditor&type=images',
                    //'filebrowserFlashBrowseUrl' => '/admin/kcfinder/browse.php?opener=ckeditor&type=flash',
                    //'filebrowserUploadUrl' => '/admin/kcfinder/upload.php?opener=ckeditor&type=files',
                    'filebrowserImageUploadUrl' => '/admin/kcfinder/upload.php?opener=ckeditor&type=images',
                    //'filebrowserFlashUploadUrl' => '/admin/kcfinder/upload.php?opener=ckeditor&type=flash',
                )))
            ->add('titleEn', TextType::class, array(
                'label' => 'Tytyuł (en)',
                'attr'  => array('style' => 'width: 100%;'),
                'required' => true,
            ))->add('contentEn', CKEditorType::class, array(
                'label' => 'Treść (en)',
                'config' => array(
                    //'filebrowserBrowseUrl' => '/admin/kcfinder/browse.php?opener=ckeditor&type=files',
                    'filebrowserImageBrowseUrl' => '/admin/kcfinder/browse.php?opener=ckeditor&type=images',
                    //'filebrowserFlashBrowseUrl' => '/admin/kcfinder/browse.php?opener=ckeditor&type=flash',
                    //'filebrowserUploadUrl' => '/admin/kcfinder/upload.php?opener=ckeditor&type=files',
                    'filebrowserImageUploadUrl' => '/admin/kcfinder/upload.php?opener=ckeditor&type=images',
                    //'filebrowserFlashUploadUrl' => '/admin/kcfinder/upload.php?opener=ckeditor&type=flash',
                )
            ));
    }

    public function getBlockPrefix()
    {
        return 'admin_newsletter';
    }
}