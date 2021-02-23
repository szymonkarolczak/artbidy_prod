<?php

namespace AdminBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewsLangType extends AbstractType
{
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('lang', EntityType::class, array(
            'translation_domain' => 'admin',
            'label' => 'admin.jezyk',
            'class' => 'AppBundle:Language',
            'choice_label' => 'shortcut'
        ))->add('title', TextType::class, array(
            'label'    => 'admin.tytul',
            'translation_domain' => 'admin'
        ))->add('metaTitle', TextType::class, array(
            'label'    => 'admin.metatitle',
            'translation_domain' => 'admin'
        ))->add('small_text', CKEditorType::class, array(
            'label'    => 'aktualnosci.tresc_wstepna',
            'translation_domain' => 'admin'
        ))->add('text', CKEditorType::class, array(
            'label'    => 'aktualnosci.tresc_glowna',
            'translation_domain' => 'admin'
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\NewsLang',
        ));
    }

    public function getBlockPrefix()
    {
        return 'admin_news_lang';
    }
}