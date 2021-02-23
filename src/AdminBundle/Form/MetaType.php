<?php

namespace AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class MetaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('metaTitle', TextType::class, array(
            'label'    => false,
            'translation_domain' => 'admin'
        ));
    }

    public function getBlockPrefix()
    {
        return 'admin_meta';
    }
}