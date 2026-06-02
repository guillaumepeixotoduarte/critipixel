<?php

namespace App\Doctrine\DataFixtures;

use App\Model\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use function array_fill_callback;

final class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $users = array_fill_callback(0, 10, function (int $index) {
            $user = (new User)
                ->setEmail(sprintf('user+%d@email.com', $index))
                ->setPlainPassword('password')
                ->setUsername(sprintf('user+%d', $index));

            // 💡 On ajoute la référence à la volée
            $this->addReference('user_' . $index, $user);

            return $user;
        });

        array_walk($users, [$manager, 'persist']);

        $manager->flush();
    }
}
