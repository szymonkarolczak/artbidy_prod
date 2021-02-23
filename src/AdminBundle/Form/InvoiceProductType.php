<?php

namespace AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvoiceProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', TextType::class, array(
            'label'    => 'faktury.produkt.tytul',
            'translation_domain' => 'admin'
        ))->add('qty', TextType::class, array(
            'label'    => 'faktury.produkt.ilosc',
            'translation_domain' => 'admin'
        ))->add('netto', TextType::class, array(
            'label'    => 'faktury.produkt.cena_netto',
            'translation_domain' => 'admin'
        ));
    }
}