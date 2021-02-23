<?php

namespace AdminBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BannerLangType extends AbstractType
{
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('lang', EntityType::class, array(
            'translation_domain' => 'admin',
            'label' => 'admin.jezyk',
            'class' => 'AppBundle:Language',
            'choice_label' => 'shortcut'
        ))->add('head', TextType::class, array(
            'label'    => 'Nagłówek',
            'required' => false
        ))->add('mainText', TextType::class, array(
            'label'    => 'Tytuł'
        ))->add('description', TextType::class, array(
            'label'    => 'Opis'
        ))->add('buttonText', TextType::class, array(
            'label' => 'Tekst przycisku'
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AdminBundle\Entity\BannerLang',
        ));
    }

    public function getBlockPrefix()
    {
        return 'admin_banner_lang';
    }
}