<?php

namespace AppBundle\Form;

use AdminBundle\Form\HouseAuctionLangType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HouseAuctionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('langs', CollectionType::class, array(
            'entry_type' => HouseAuctionLangType::class,
            'label' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'translation_domain' => 'admin'
        ))->add('address', TextType::class, array(
            'label'    => 'user.adres',
        ))->add('city', TextType::class, array(
            'label'    => 'user.miasto',
        ))->add('image', FileType::class, array(
            'label'    => 'add_work.grafika',
            'required' => !$options['admin']
        ))->add('startDate', DateType::class, array(
            'label'    => 'main.data_start',
            'years' => range(date('Y'), date('Y')+2)
        ))->add('submit', SubmitType::class, array(
            'label'    => 'main.zapisz',
            'attr' => array('class' => 'btn-success')
        ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'admin'       => false,
        ));
    }

    public function getBlockPrefix()
    {
        return 'admin_house_auction';
    }
}