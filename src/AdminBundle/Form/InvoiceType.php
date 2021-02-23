<?php

namespace AdminBundle\Form;

use AppBundle\Repository\CurrencyRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class InvoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('buyer', TextareaType::class, array(
            'label'    => 'faktury.nabywca',
            'translation_domain' => 'admin'
        ))->add('tax', IntegerType::class, array(
            'label'    => 'faktury.podatek',
            'translation_domain' => 'admin'
        ))->add('currency', EntityType::class, array(
            'label'    => 'main.waluta',
            'class'  => 'AppBundle:Currency',
            'query_builder' => function (CurrencyRepository $er) {
                return $er->createQueryBuilder('c')
                    ->where('c.enabled = :enabled')
                    ->setParameter(':enabled', true);
            },
            'choice_label' => 'code'
        ))->add('number', TextType::class, array(
            'label'    => 'faktury.numer',
            'attr' => array('readonly' => 'readonly'),
            'translation_domain' => 'admin'
        ))->add('sellDate', DateType::class, array(
            'label'    => 'faktury.data_sprzedazy',
            'translation_domain' => 'admin'
        ))->add('products', CollectionType::class, array(
            'entry_type' => InvoiceProductType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'label' => false
        ));
    }

    public function getBlockPrefix()
    {
        return 'admin_invoice';
    }
}