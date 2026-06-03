<?php

declare(strict_types=1);

namespace App\Tests\Functional\VideoGame;

use App\Model\Entity\Tag;
use App\Model\Entity\VideoGame;
use App\Tests\Functional\FunctionalTestCase;
use Doctrine\ORM\EntityManagerInterface;

final class FilterTest extends FunctionalTestCase
{
    public function testShouldListTenVideoGames(): void
    {
        $this->get('/');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');
        $this->client->clickLink('2');
        self::assertResponseIsSuccessful();
    }

    public function testShouldFilterVideoGamesBySearch(): void
    {
        $this->get('/');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');
        $this->client->submitForm('Filtrer', ['filter[search]' => 'Jeu vidéo 49'], 'GET');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'article.game-card');
    }

    public function testShouldFilterVideoGamesBySearchDoesntExist(): void
    {
        $this->get('/');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');
        $this->client->submitForm('Filtrer', ['filter[search]' => 'Jeu vidéo Inexistant'], 'GET');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(0, 'article.game-card');
    }

    public function testShouldFilterVideoGamesByTitle(): void
    {
        $this->get('/');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');
        $crawler = $this->client->submitForm('Trier', ['sorting' => 'Title', 'direction' => 'Descending'], 'GET');
        self::assertResponseIsSuccessful();
        $titles = $crawler->filter('.card-body .game-card-title a')->extract(['_text']);

        $videoGame = $this->service(EntityManagerInterface::class)->getRepository(VideoGame::class)->createQueryBuilder('v')
            ->orderBy('v.title', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        self::assertInstanceOf(VideoGame::class, $videoGame);

        self::assertEquals($titles[0], $videoGame->getTitle());
        self::assertSelectorCount(10, 'article.game-card');
    }

    public function testShouldFilterVideoGamesByTag(): void
    {

        $this->get('/');

        $em = $this->service(EntityManagerInterface::class);
        $tags = $em->getRepository(Tag::class)->findAll();

        $videoGames = $em->getRepository(VideoGame::class)->createQueryBuilder('v')
            ->join('v.tags', 't')
            ->where('t.id = :tagId')
            ->setParameter('tagId', $tags[0]->getId())
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $expectedCount = count($videoGames);

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');

        $tag_id = (string) $tags[0]->getId();

        $form = $this->client->getCrawler()->selectButton('Filtrer')->form();
        
        $form['filter[search]'] = '';
        $form['filter[tags]'] = [$tag_id];

        $crawler = $this->client->submit($form);
        self::assertResponseIsSuccessful();
        self::assertSelectorCount($expectedCount, 'article.game-card');

        $expectedTitles = array_map(function (VideoGame $game) {
            return $game->getTitle();
        }, $videoGames);

        $actualTitles = $crawler->filter('.card-body .game-card-title a')->extract(['_text']);

        self::assertSame(
            $expectedTitles, 
            $actualTitles, 
            "La liste des jeux affichés ou leur ordre ne correspond pas aux données de la base de données."
        );
    }


    public function testShouldFilterVideoGamesByMultipleTags(): void
    {
        $this->get('/');

        $em = $this->service(EntityManagerInterface::class);
        $tags = $em->getRepository(Tag::class)->findAll();

        $tagIds = [
            $tags[0]->getId(),
            $tags[1]->getId(),
        ];

        $videoGames = $em->getRepository(VideoGame::class)->createQueryBuilder('v')
            ->join('v.tags', 't')
            ->where('t.id IN (:tagIds)')
            ->setParameter('tagIds', $tagIds)
            ->groupBy('v.id')
            ->having('COUNT(distinct t.id) = :count')
            ->setParameter('count', count($tagIds))
            ->getQuery()
            ->getResult();

        $expectedCount = count($videoGames);

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');

        $tag_id = (string) $tags[0]->getId();
        $tag_id2 = (string) $tags[1]->getId();

        $form = $this->client->getCrawler()->selectButton('Filtrer')->form();
        
        $form['filter[search]'] = '';
        $form['filter[tags]'] = [$tag_id, $tag_id2];

        $crawler = $this->client->submit($form);
        self::assertResponseIsSuccessful();
        self::assertSelectorCount($expectedCount, 'article.game-card');

        $expectedTitles = array_map(function (VideoGame $game) {
            return $game->getTitle();
        }, $videoGames);

        $actualTitles = $crawler->filter('.card-body .game-card-title a')->extract(['_text']);

        self::assertSame(
            $expectedTitles, 
            $actualTitles, 
            "La liste des jeux affichés ou leur ordre ne correspond pas aux données de la base de données."
        );
    }


     public function testShouldFilterVideoGamesByInexistantTag(): void
    {
        $this->get('/');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');

        $this->client->request('GET', '/', [
            'filter' => [
                'search' => '',
                'tags' => ['999999'] // On force un ID inexistant
            ]
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card'); // Si le tag n'existe pas, le filtrage avec le form, ignorera le tag et affichera tous les jeux vidéo
    }
}
