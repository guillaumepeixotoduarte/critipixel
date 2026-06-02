<?php

declare(strict_types=1);

namespace App\Tests\Functional\VideoGame;

use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Tests\Functional\FunctionalTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class ReviewTest extends FunctionalTestCase
{

    public function testAddReviewToVideoGame(): void
    {
        $this->login();

        $em = $this->service(EntityManagerInterface::class);

        $currentUser = $em->getRepository(User::class)->findOneBy(['email' => 'user+0@email.com']);

        $randomGame = $em->getRepository(VideoGame::class)->createQueryBuilder('v')
            ->leftJoin('v.reviews', 'r', 'WITH', 'r.user = :user')
            ->where('r.id IS NULL')
            ->setParameter('user', $currentUser)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        $url = '/' . $randomGame[0]->getSlug();
        $nbReviews = count($randomGame[0]->getReviews());
        $crawler =$this->get($url);

        self::assertSelectorCount($nbReviews, '#pane-reviews .list-group .list-group-item', 'Le nombre de reviews affichées doit correspondre au nombre de reviews du jeu vidéo');

        $form = $crawler->selectButton('Poster')->form();
    
        $form['review[rating]'] = '4';
        $form['review[comment]'] = 'Great game!';

        $this->client->submit($form);

        self::assertResponseRedirects($url); // vérifie qu'on redirige vers la bonne URL
        $crawler = $this->client->followRedirect(); 

        self::assertResponseIsSuccessful();
        self::assertSelectorCount($nbReviews + 1, '#pane-reviews .list-group .list-group-item', 'Après l\'ajout d\'une review, le nombre de reviews affichées doit être incrémenté de 1');
    }
}