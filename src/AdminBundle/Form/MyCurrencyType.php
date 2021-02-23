<?php

namespace AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class MyCurrencyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('code', CurrencyType::class, array(
            'label'    => 'ogolne.waluty.kod',
            'translation_domain' => 'admin'
        ))->add('enabled', CheckboxType::class, array(
            'label'    => 'admin.aktywnosc',
            'translation_domain' => 'admin',
            'required' => false
        ));
    }

    public function getBlockPrefix()
    {
        return 'admin_currency';
    }
}