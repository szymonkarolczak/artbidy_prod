<?php

namespace AdminBundle\Form\DataTransformer;

use UserBundle\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class UserToUsernameTransformer implements DataTransformerInterface
{
    
    private $manager;
    
    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }
    
    /**
     * Transforms an object (user) to a string (number).
     *
     * @param  User|null $user
     * @return string
     */
    public function transform($user)
    {
        $array = new \Doctrine\Common\Collections\ArrayCollection();
        if (null === $user->toArray()) {
            return $array;
        }
        
        foreach($user->toArray() as $user)
        {
            $array->add($user->getUsername());
        }
        
        return $array;
    }

    /**
     * Transforms a string (number) to an object (user).
     *
     * @param  string $usersIds
     * @return User|null
     * @throws TransformationFailedException if object (user) is not found.
     */
    public function reverseTransform($usersIds)
    {
        $array = new \Doctrine\Common\Collections\ArrayCollection();
        if (empty($usersIds)) {
            return $array;
        }

        foreach($usersIds as $id)
        {
            $user = $this->manager
                ->getRepository('UserBundle:User')
                ->findBy(array('username' => $id));
            
            if (null === $user) {
                // causes a validation error
                // this message is not shown to the user
                // see the invalid_message option
                throw new TransformationFailedException(sprintf(
                    'An user with id "%s" does not exist!',
                    $id
                ));
            }
            
            $array->add($user[0]);
        }

        return $array;
    }
}
