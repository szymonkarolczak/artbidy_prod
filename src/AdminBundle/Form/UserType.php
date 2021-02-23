<?php

namespace AdminBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Form\CallbackTransformer;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('username', TextType::class, array(
            'label'    => 'uzytkownicy.login',
            'translation_domain' => 'admin'
        ))->add('slug', TextType::class, array(
            'label'     =>  'slug',
            'translation_domain' => 'admin',
            'required' => true,
        ))->add('fullname', TextType::class, array(
            'label'    => 'uzytkownicy.imie_nazwisko_nazwa',
            'translation_domain' => 'admin'
        ))->add('birthdate', BirthdayType::class, array(
            'label'    => 'uzytkownicy.data_urodzenia',
            'translation_domain' => 'admin',
            'widget' => 'text',
            'placeholder' => array(
                'year' => 'Year', 'month' => 'Month', 'day' => 'Day',
            ),
            'required' => false
        ))->add('deathdate', BirthdayType::class, array(
            'label'    => 'uzytkownicy.data_smierci',
            'translation_domain' => 'admin',
            'widget' => 'text',
            'required' => false,
            'placeholder' => array(
                'year' => 'Year', 'month' => 'Month', 'day' => 'Day',
            ),
            'required' => false
        ))->add('roleEnd', DateTimeType::class, array(
            'label'    => 'uzytkownicy.data_wygasniecia',
            'translation_domain' => 'admin',
            'required' => false
        ))->add('annoucement', DateTimeType::class, array(
            'label'    => 'uzytkownicy.pakiet.ogloszenia',
            'translation_domain' => 'admin',
            'required' => false
        ))->add('database', DateTimeType::class, array(
            'label'    => 'uzytkownicy.pakiet.baza_danych',
            'translation_domain' => 'admin',
            'required' => false
        ))->add('image', FileType::class, array(
            'label' => false,
            'required' => false
        ))->add('email', EmailType::class, array(
            'label'    => 'uzytkownicy.email',
            'translation_domain' => 'admin'
        ))->add('phone', TextType::class, array(
            'label'    => 'user.numer_telefonu'
        ))->add('enabled', CheckboxType::class, array(
            'label'    => 'uzytkownicy.konto_aktywne',
            'translation_domain' => 'admin',
            'required' => false
        ))->add('newsletter', CheckboxType::class, array(
            'label'    => 'uzytkownicy.newsletter',
            'translation_domain' => 'admin',
            'required' => false
        ))->add('pinned', CheckboxType::class, array(
            'label'    => 'aktualnosci.wyroznione',
            'translation_domain' => 'admin',
            'required' => false
        ))->add('roles', ChoiceType::class, array(
            'label'    => 'uzytkownicy.ranga',
            'translation_domain' => 'admin',
            'choices' => $this->flattenArray($options['roles']),
            'expanded' => true,
            'multiple' => true,
            'required' => false
        ))->add('country', CountryType::class, array(
            'label' => 'user.kraj'
        ))->add('city', TextType::class, array(
            'label' => 'user.miasto',
            'required' => false
        ))->add('card', CollectionType::class, array(
            'entry_type' => UserCard::class,
            'label'    => false
        ))->add('metatitle', TextType::class, array(
            'label' => 'user.metatitle',
            'translation_domain' => 'admin',
            'required' => false
        ))->add('metatitleEn', TextType::class, array(
            'label' => 'user.metatitleEn',
            'translation_domain' => 'admin',
            'required' => false
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
        return 'admin_user';
    }

    private function flattenArray(array $data)
    {
        $returnData = array();

        foreach($data as $key => $value)
        {
            $tempValue = str_replace("ROLE_", '', $key);
            $tempValue = ucwords(strtolower(str_replace("_", ' ', $tempValue)));
            $returnData[$key] = $tempValue;
        }
        return array_flip($returnData);
    }
}
