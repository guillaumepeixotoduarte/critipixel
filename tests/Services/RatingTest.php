<?php
namespace App\Tests\Services;
use App\Model\Entity\Review;
use App\Model\Entity\VideoGame;
use App\Rating\RatingHandler;
use PHPUnit\Framework\TestCase;



class RatingTest extends TestCase
{
    public function testCalculateAverageWithReviews(): void
    {
        $videoGame = new VideoGame();

        $review1 = (new Review())->setRating(5);
        $review2 = (new Review())->setRating(4);
        $review3 = (new Review())->setRating(2);

        $videoGame->getReviews()->add($review1);
        $videoGame->getReviews()->add($review2);
        $videoGame->getReviews()->add($review3);

        $calculateAverageRating = new RatingHandler();

        $calculateAverageRating->calculateAverage($videoGame);

        self::assertEquals(4, $videoGame->getAverageRating());
    }

    public function testCalculateAverageWhenNoReviews(): void
    {
        $videoGame = new VideoGame();
        $ratingHandler = new RatingHandler();

        $ratingHandler->calculateAverage($videoGame);

        self::assertNull($videoGame->getAverageRating());
    }

    public function testCountRatingsPerValueWithDifferentNumbersOfReviewsRatings(): void
    {
        // 1. GIVEN : On prépare notre jeu vidéo
        $videoGame = new VideoGame();

        // On crée un échantillon précis de notes pour tester le "match"
        $review1 = (new Review())->setRating(1);
        $review2 = (new Review())->setRating(2);
        $review3 = (new Review())->setRating(2); // Deux fois la note 2
        $review4 = (new Review())->setRating(4);
        $review5 = (new Review())->setRating(5);

        // On injecte manuellement dans la collection comme tu as fait
        $videoGame->getReviews()->add($review1);
        $videoGame->getReviews()->add($review2);
        $videoGame->getReviews()->add($review3);
        $videoGame->getReviews()->add($review4);
        $videoGame->getReviews()->add($review5);

        $ratingHandler = new RatingHandler();

        // 2. WHEN : On lance le compte
        $ratingHandler->countRatingsPerValue($videoGame);

        // 3. THEN : On vérifie que les compteurs correspondent à nos injections
        $stats = $videoGame->getNumberOfRatingsPerValue();

        self::assertEquals(1, $stats->getNumberOfOne());   // 1 seule review de note 1
        self::assertEquals(2, $stats->getNumberOfTwo());   // 2 reviews de note 2
        self::assertEquals(0, $stats->getNumberOfThree()); // 1 seule review de note 3
        self::assertEquals(1, $stats->getNumberOfFour());  // 1 seule review de note 4
        self::assertEquals(1, $stats->getNumberOfFive());  // 1 seule review de note 5
    }

    public function testCountRatingsPerValueWhenNoReviews(): void
    {
        $videoGame = new VideoGame();
        $ratingHandler = new RatingHandler();

        $ratingHandler->countRatingsPerValue($videoGame);

        $stats = $videoGame->getNumberOfRatingsPerValue();

        self::assertEquals(0, $stats->getNumberOfOne());
        self::assertEquals(0, $stats->getNumberOfTwo());
        self::assertEquals(0, $stats->getNumberOfThree());
        self::assertEquals(0, $stats->getNumberOfFour());
        self::assertEquals(0, $stats->getNumberOfFive());
    }
}