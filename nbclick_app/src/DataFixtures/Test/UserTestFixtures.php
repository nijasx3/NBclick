<?php
namespace App\DataFixtures\Test;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserTestFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void  // Ajout du type de retour void
    {
        $user = new User();
        $user->setEmail('test@test.fr');
        $user->setNameUser('testuser');
        $user->setFirstNameUser('Initial Name');
        $user->setCashBalanceUser(100);
        
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            'password123'
        );
        $user->setPassword($hashedPassword);

        $manager->persist($user);
        $manager->flush();

        $this->addReference('test-user', $user);
    }
}