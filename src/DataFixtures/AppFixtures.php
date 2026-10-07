<?php

namespace App\DataFixtures;

use App\Data\GrilleTarifaire;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Données de démonstration (développement uniquement) : grille tarifaire + admin de dev.
 *
 *   php bin/console doctrine:fixtures:load
 *
 * Admin de dev : admin@lesdeuxweb.test / admin-dev-lesdeuxweb
 */
class AppFixtures extends Fixture
{
    public const ADMIN_EMAIL = 'admin@lesdeuxweb.test';
    public const ADMIN_MOT_DE_PASSE = 'admin-dev-lesdeuxweb';

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        foreach (GrilleTarifaire::creerEntites() as $entite) {
            $manager->persist($entite);
        }

        $admin = (new User())->setEmail(self::ADMIN_EMAIL)->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, self::ADMIN_MOT_DE_PASSE));
        $manager->persist($admin);

        $manager->flush();
    }
}
