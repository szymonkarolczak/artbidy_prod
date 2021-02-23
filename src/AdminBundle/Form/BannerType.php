<?php

namespace AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BannerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('langs', CollectionType::class, array(
            'entry_type' => BannerLangType::class,
            'label' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'translation_domain' => 'admin'
        ))->add('url', UrlType::class, array(
            'label' => 'Url'
        ))->add('color', ChoiceType::class, array(
            'label' => 'Kolor tekstu',
            'choices' => array(
                'Biały' => 'white',
                'Czarny' => 'black'
            )
        ))->add('image', FileType::class, array(
            'label' => 'Grafika',
            'required' => !$options['edit']
        ))->add('position', IntegerType::class, array(
            'label' => 'Pozycja',
            'required' => false
        ))->add('active', CheckboxType::class, array(
            'label' => 'Aktywność',
            'required' => false
        ))->add('submit', SubmitType::class, array(
            'label' => 'Wyślij',
            'attr' => array('class' => 'btn-success')
        ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AdminBundle\Entity\Banner',
            'edit' => false
        ));
    }
}