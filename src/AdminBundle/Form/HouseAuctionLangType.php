<?php

namespace AdminBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HouseAuctionLangType extends AbstractType
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
        ))->add('description', TextareaType::class, array(
            'label'    => 'admin.opis',
            'translation_domain' => 'admin'
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\HouseAuctionLang',
        ));
    }

    public function getBlockPrefix()
    {
        return 'admin_houseauction_lang';
    }
}