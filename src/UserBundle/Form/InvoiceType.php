<?php

namespace UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class InvoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('invoice_fullname', TextType::class, array(
            'label'    => 'profile.ustawienia.faktura_nazwa',
        ))->add('invoice_address', TextType::class, array(
            'label'    => 'user.adres',
        ))->add('invoice_postal_code', TextType::class, array(
            'label'    => 'user.kod_pocztowy',
        ))->add('invoice_city', TextType::class, array(
            'label'    => 'user.miasto',
        ))->add('invoice_country', TextType::class, array(
            'label'    => 'user.kraj',
        ))->add('invoice_nip', TextType::class, array(
            'label'    => 'user.nip',
            'required' => false,
        ))->add('submit', SubmitType::class, array(
            'label'    => 'main.zapisz',
            'attr' => array('class' => 'btn-success')
        ));
    }

    public function getBlockPrefix()
    {
        return 'app_user_settings_invoice';
    }
}