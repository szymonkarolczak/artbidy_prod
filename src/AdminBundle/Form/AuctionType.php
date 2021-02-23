<?php

namespace AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use AppBundle\Repository\CurrencyRepository;

class AuctionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('langs', CollectionType::class, array(
            'entry_type' => AuctionLangType::class,
            'label' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'translation_domain' => 'admin'
        ))->add('image', FileType::class, array(
            'label'    => 'admin.grafika',
            'translation_domain' => 'admin',
            'required' => false,
            'label' => false
        ))->add('startDate', DateTimeType::class, array(
            'label'    => 'aukcje.data_rozpoczecia',
            'translation_domain' => 'admin'
        ))->add('endDate', DateTimeType::class, array(
            'label'    => 'aukcje.data_zakonczenia',
            'translation_domain' => 'admin'
        ))->add('increment', CollectionType::class, array(
            'label'    => 'aukcje.postapienie',
            'translation_domain' => 'admin',
            'allow_add' => true,
            'allow_delete'=>true,
            'prototype' => true,
            'required' => false
        ))->add('customStartPrice', CheckboxType::class, array(
            'label'    => 'aukcje.dowolna_cena_wywolawcza',
            'translation_domain' => 'admin',
            'required' => false,
        ))->add('enabled', CheckboxType::class, array(
            'label'    => 'admin.aktywnosc',
            'translation_domain' => 'admin',
            'required' => false,
        ))->add('pinned', CheckboxType::class, array(
            'label'    => 'aktualnosci.wyroznione',
            'translation_domain' => 'admin',
            'required' => false,
        ))->add('currency', EntityType::class, array(
            'label'    => 'main.waluta',
            'class'  => 'AppBundle:Currency',
            'attr'  => array('style' => 'max-width: 100px; width: 100%'),
            'query_builder' => function (CurrencyRepository $er) {
                return $er->createQueryBuilder('c')
                    ->where('c.enabled = :enabled')
                    ->setParameter(':enabled', true);
            },
            'choice_label' => 'code'
        ))->add('startPrice', TextType::class, array(
            'label'    => 'aukcje.ceny_wywolawcze',
            'translation_domain' => 'admin',
            'attr'  => array('style' => 'width: 10%;'),
            'required' => false,
        ))->add('buyNow', CheckboxType::class, array(
            'label'    => 'aukcje.kup_teraz',
            'translation_domain' => 'admin',
            'required' => false,
        ));
    }

    public function getBlockPrefix()
    {
        return 'admin_auction';
    }
}