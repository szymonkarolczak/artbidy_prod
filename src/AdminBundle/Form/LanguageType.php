<?php

namespace AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class LanguageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('shortcut', TextType::class, array(
            'label'    => 'ogolne.jezyki.skrot',
            'translation_domain' => 'admin'
        ))->add('enabled', CheckboxType::class, array(
            'label'    => 'admin.aktywnosc',
            'translation_domain' => 'admin',
            'required' => false,
            'attr'     => array('checked' => 'checked'),
        ));
    }

    public function getBlockPrefix()
    {
        return 'admin_language';
    }
}