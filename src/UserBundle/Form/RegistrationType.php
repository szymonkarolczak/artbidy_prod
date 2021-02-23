<?php

namespace UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Intl\Intl;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('country', CountryType::class, array(
            'label' => 'user.kraj'
        ))->add('fullname', TextType::class, array(
            'label' => 'register.fullname'
        ))->add('phone', TextType::class, array(
            'label' => 'user.numer_telefonu',
            'required' => true,
        ))->add('newsletter', CheckboxType::class, array(
            'label'    => 'register.akceptuje_newsletter',
            'required' => false,
        ))->add('terms', CheckboxType::class, array(
            'label'    => 'register.akceptuje_regulamin',
            'required' => true,
            'mapped' => false
        ));
        
        $builder->get('country')->addModelTransformer(new CallbackTransformer(
            function ($countryName) {
                $countries = Intl::getRegionBundle()->getCountryNames();
                $countries = array_flip($countries);
                return isset($countries[$countryName]) ? $countries[$countryName] : null;
            },
            function ($code) {
                return Intl::getRegionBundle()->getCountryName($code);
            }
        ));
    }

    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\RegistrationFormType';
    }

    public function getBlockPrefix()
    {
        return 'app_user_registration';
    }
}