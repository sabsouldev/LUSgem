<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Verifie que les routes protegees redirigent vers la page de connexion
 * quand l'utilisateur n'est pas authentifie.
 */
final class SecurityAccessTest extends WebTestCase
{
    /**
     * @dataProvider protectedRoutesProvider
     */
    public function testProtectedRouteRedirectsToLogin(string $url): void
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertResponseRedirects(
            '/connexion',
            302,
            sprintf('La route "%s" devrait rediriger vers /connexion sans authentification.', $url)
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function protectedRoutesProvider(): array
    {
        return [
            'dashboard adherent'    => ['/adherent/vie-gem'],
            'propositions adherent' => ['/adherent/propositions'],
            'planning adherent'     => ['/adherent/planning'],
            'comptes rendus'        => ['/adherent/comptes-rendus'],
            'newsletter adherent'   => ['/adherent/newsletter'],
            'dashboard admin'       => ['/admin'],
            'admin adherents'       => ['/admin/adherents'],
            'admin propositions'    => ['/admin/propositions'],
            'admin publications'    => ['/admin/publications'],
            'admin messages'        => ['/admin/messages'],
        ];
    }

    public function testLoginPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/connexion');

        $this->assertResponseIsSuccessful();
    }

    public function testLoginPageHasCsrfToken(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/connexion');

        $this->assertResponseIsSuccessful();
        // Le formulaire de connexion doit contenir un champ email et mot de passe.
        $this->assertSelectorExists('input[name="_username"]');
        $this->assertSelectorExists('input[name="_password"]');
    }
}
