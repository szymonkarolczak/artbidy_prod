<?php

namespace AppBundle\Form;

use AdminBundle\Form\DataTransformer\UserToUsernameTransformer;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LookingType extends AbstractType
{
    private $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('target', TextType::class, array(
            'label'    => false,
            'attr' => array('placeholder' => 'user.imie_i_nazwisko')
        ))->add('submit', SubmitType::class, array(
            'label' => 'exhibition.dodaj_nowego_artyste',
            'attr' => array('class' => 'btn btn-success')
        ));

        $builder->get('target')->addModelTransformer(new CallbackTransformer(
            function($user) {
                return $user;
            }, function($username) {
                $user = $this->manager
                    ->getRepository('UserBundle:User')
                    ->findBy(array('username' => $username));
                if (null === $user) {
                    throw new TransformationFailedException(sprintf(
                        'An user with username "%s" does not exist!',
                        $username
                    ));
                }
                return $user[0];
            }
        ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\UserLooking'
        ));
    }

    public function getBlockPrefix()
    {
        return 'user_looking';
    }
}