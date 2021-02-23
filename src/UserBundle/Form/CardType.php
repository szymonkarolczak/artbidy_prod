<?php

namespace UserBundle\Form;

use AdminBundle\Form\UserCard;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if(in_array('ROLE_ARTYSTA', $options['roles']) ||
            in_array('ROLE_REDAKTOR', $options['roles']) ||
            in_array('ROLE_ADMIN', $options['roles']))
        {
            $birthdayLabel = 'user.data_urodzenia';
        } else {
            $birthdayLabel = 'user.data_zalozenia';
        }

        $builder->add('image', FileType::class, array(
            'label'    => 'profile.ustawienia.zdjecie_profilowe',
            'required' => false,
        ))->add('country', CountryType::class, array(
            'label'    => 'user.kraj',
            'required' => false,
        ))->add('fullname', TextType::class, array(
            'label'    => 'profile.ustawienia.faktura_nazwa',
            'required' => false,
        ))->add('address', TextType::class, array(
            'label'    => 'user.adres',
            'required' => false,
        ))->add('city', TextType::class, array(
            'label'    => 'user.miasto',
            'required' => false,
        ))->add('website', UrlType::class, array(
            'label'    => 'user.strona_www',
            'required' => false,
        ))->add('googleMaps', TextType::class, array(
            'label'    => 'user.wspolrzedne',
            'required' => false,
            'attr' => array('placeholder' => 'lattitude,longitude')
        ))->add('birthdate', BirthdayType::class, array(
            'label'    => $birthdayLabel,
            'required' => false,
        ))->add('card', CollectionType::class, array(
            'entry_type' => UserCard::class,
            'label'    => 'user.card'
        ))->add('email', EmailType::class, array(
            'label' => 'form.email', 
            'translation_domain' => 'FOSUserBundle',
            'required' => true,
        ))->add('phone', TextType::class, array(
            'label' => 'user.numer_telefonu',
            'required' => false,
        ))->add('submit', SubmitType::class, array(
            'label'    => 'main.zapisz',
            'attr' => array('class' => 'btn btn-success')
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
        return 'app_user_settings_card';
    }
}