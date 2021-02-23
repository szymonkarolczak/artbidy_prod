<?php

namespace AppBundle\Form;

use AdminBundle\Form\UserCard;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('fullname', TextType::class, array(
            'label'    => 'user.imie_i_nazwisko'
        ))->add('birthdate', BirthdayType::class, array(
            'label'    => 'uzytkownicy.data_urodzenia',
            'translation_domain' => 'admin',
            'widget' => 'single_text',
            'attr' => array('placeholder' => 'YYYY-MM-DD')
        ))->add('deathdate', BirthdayType::class, array(
            'label'    => 'uzytkownicy.data_smierci',
            'translation_domain' => 'admin',
            'widget' => 'single_text',
            'required' => false,
            'attr' => array('placeholder' => 'YYYY-MM-DD')
        ))->add('country', CountryType::class, array(
            'label' => 'user.kraj'
        ))->add('image', FileType::class, array(
            'label' => false
        ))->add('card', CollectionType::class, array(
            'entry_type' => UserCard::class,
            'label'    => false
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
        return 'user_add';
    }
}