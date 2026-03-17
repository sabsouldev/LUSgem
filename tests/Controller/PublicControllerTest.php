<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Verifie que toutes les pages publiques sont accessibles sans authentification.
 */
final class PublicControllerTest extends WebTestCase
{
    /**
     * @dataProvider publicPagesProvider
     */
    public function testPublicPageIsAccessible(string $url): void
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertResponseIsSuccessful(
            sprintf('La page "%s" devrait retourner HTTP 200.', $url)
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function publicPagesProvider(): array
    {
        return [
            'accueil'        => ['/'],
            'fonctionnement' => ['/fonctionnement'],
            'charte'         => ['/charte-cadre'],
            'activites'      => ['/activites'],
            'mooks'          => ['/mooks'],
            'blog'           => ['/blog-newsletter'],
            'contact'        => ['/contact'],
        ];
    }

    public function testContactPageHasCsrfToken(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/contact');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('input[name="_token"]');
    }

    public function testLegacyMookRouteRedirectsOrResponds(): void
    {
        $client = static::createClient();
        $client->request('GET', '/moocks');

        // La route legacy /moocks peut retourner 200 ou rediriger vers /mooks.
        $this->assertResponseStatusCodeSame(200);
    }
}
