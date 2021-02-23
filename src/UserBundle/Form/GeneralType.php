<?php

namespace UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

class GeneralType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('newsletter', CheckboxType::class, array(
            'label'    => 'register.akceptuje_newsletter',
            'required' => false,
        ))->add('email', EmailType::class, array(
            'label' => 'form.email',
            'translation_domain' => 'FOSUserBundle',
            'required' => true,
        ))->add('submit', SubmitType::class, array(
            'label'    => 'main.zapisz',
            'attr' => array('class' => 'btn btn-success')
        ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'UserBundle\Entity\User',
            'roles' => ['ROLE_USER']
        ));
    }


    public function getBlockPrefix()
    {
        return 'app_user_settings_general';
    }
}