<?php

namespace App\Doctrine\DataFixtures;

use App\Model\Entity\Review;
use App\Model\Entity\Tag;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Rating\CalculateAverageRating;
use App\Rating\CountRatingsPerValue;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

use function array_fill_callback;

final class VideoGameFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly Generator $faker,
        private readonly CalculateAverageRating $calculateAverageRating,
        private readonly CountRatingsPerValue $countRatingsPerValue
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $users = $manager->getRepository(User::class)->findAll();
        $tags = $manager->getRepository(Tag::class)->findAll();

        $videoGames = array_fill_callback(0, 50, function (int $index){
            $videoGame = (new VideoGame);
            $videoGame
                ->setTitle(sprintf('Jeu vidéo %d', $index))
                ->setDescription($this->faker->paragraph(10, true))
                ->setReleaseDate(new DateTimeImmutable())
                ->setTest($this->faker->paragraph(6, true))
                ->setRating(($index % 5) + 1)
                ->setImageName(sprintf('video_game_%d.png', $index))
                ->setImageSize(2_098_872);

            return $videoGame;
        });

        // TODO : Ajouter les tags aux vidéos 

        /** @var VideoGame $videoGame */
        foreach ($videoGames as $videoGame) {
            // On mélange le tableau de tags pour en piocher au hasard
            shuffle($tags);
            
            // On décide d'associer entre 1 et 3 tags au hasard
            $tagsCount = $this->faker->numberBetween(1, 3);
            for ($i = 0; $i < $tagsCount; $i++) {
                if (isset($tags[$i])) {
                    $videoGame->addTag($tags[$i]);
                }
            }
        }

        array_walk($videoGames, [$manager, 'persist']);

        // TODO : Ajouter des reviews aux vidéos

        /** @var VideoGame $videoGame */
        foreach($videoGames as $videoGame) {
            // On mélange le tableau d'utilisateurs pour en piocher au hasard
            shuffle($users);

            // On décide d'associer entre 0 et 5 reviews au hasard
            $reviewsCount = $this->faker->numberBetween(0, 5);
            $totalRating = 0;
            for ($i = 0; $i < $reviewsCount; $i++) {
                if (isset($users[$i])) {
                    $rating = $this->faker->numberBetween(1, 5);
                    $totalRating += $this->faker->numberBetween(1, 5);
                    $review = (new Review)
                        ->setVideoGame($videoGame)
                        ->setUser($users[$i])
                        ->setRating($rating)
                        ->setComment($this->faker->paragraph(3, true));

                    $manager->persist($review);
                    $videoGame->getReviews()->add($review);
                }
            }
            
            $this->countRatingsPerValue->countRatingsPerValue($videoGame);
            $this->calculateAverageRating->calculateAverage($videoGame);
        }

        array_walk($videoGames, [$manager, 'persist']);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class, TagsFixtures::class];
    }
}
