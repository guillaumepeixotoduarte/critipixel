<?php

declare(strict_types=1);

namespace App\Tests\Functional\Auth;

use App\Tests\Functional\FunctionalTestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class LoginTest extends FunctionalTestCase
{
    public function testThatLoginShouldSucceeded(): void
    {
        $this->get('/auth/login');

        echo $this->client->getResponse()->getContent();

        $this->client->submitForm('Se connecter', [
            'email' => 'user+1@email.com',
            'password' => 'password'
        ]);

        /** @var AuthorizationCheckerInterface $authorizationChecker */
        $authorizationChecker = $this->service(AuthorizationCheckerInterface::class);

        self::assertTrue($authorizationChecker->isGranted('IS_AUTHENTICATED'));

        $this->get('/auth/logout');

        self::assertFalse($authorizationChecker->isGranted('IS_AUTHENTICATED'));
    }

    public function testThatLoginShouldFailed(): void
    {
        $this->get('/auth/login');

        $this->client->submitForm('Se connecter', [
            'email' => 'user+1@email.com',
            'password' => 'fail'
        ]);

        /** @var AuthorizationCheckerInterface $authorizationChecker */
        $authorizationChecker = $this->service(AuthorizationCheckerInterface::class);

        self::assertFalse($authorizationChecker->isGranted('IS_AUTHENTICATED'));
    }
}
