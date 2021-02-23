<?php

namespace AdminBundle\Form;

use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

class UserCard extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('lang', HiddenType::class, array(
            'translation_domain' => 'admin',
            'label' => false,
            'attr' => array('readonly' => 'readonly')
        ))->add('content', CKEditorType::class, array(
            'label' => false
        ));
    }

    public function getBlockPrefix()
    {
        return 'admin_user_card';
    }
}