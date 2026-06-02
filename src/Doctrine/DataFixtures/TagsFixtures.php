<?php

namespace App\Doctrine\DataFixtures;

use App\Model\Entity\Tag;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

class TagsFixtures extends Fixture
{

    public function load(ObjectManager $manager): void
    {

        $tags = array_fill_callback(0, 50, function (int $index) {

            $tag = (new Tag);
            $tag->setName(sprintf('Tag %d', $index));
            return $tag;
        });

        array_walk($tags, [$manager, 'persist']);

        $manager->flush();
    }
}
