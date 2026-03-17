<?php

namespace App\Tests\Controller;

use App\Entity\Proposition;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels pour AdherentPropositionController et AdminPropositionController.
 * Les tests @group db necessitent la base de test (voir .env.test + doctrine:schema:create --env=test).
 */
final class PropositionControllerTest extends WebTestCase
{
    // -------------------------------------------------------------------------
    // Tests sans authentification (ne necessitent pas de base de donnees)
    // -------------------------------------------------------------------------

    public function testAdherentPropositionsRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/adherent/propositions');

        $this->assertResponseRedirects('/connexion');
    }

    public function testAdminPropositionsRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/propositions');

        $this->assertResponseRedirects('/connexion');
    }

    public function testAdminPropositionShowRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/propositions/1');

        $this->assertResponseRedirects('/connexion');
    }

    public function testAdherentDeleteRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('POST', '/adherent/propositions/1/supprimer');

        $this->assertResponseRedirects('/connexion');
    }

    public function testAdminDeleteRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('POST', '/admin/propositions/1/supprimer');

        $this->assertResponseRedirects('/connexion');
    }

    // -------------------------------------------------------------------------
    // Tests authentifies (necessitent base de donnees de test)
    // -------------------------------------------------------------------------

    /**
     * @group db
     */
    public function testAdherentCanViewHisPropositions(): void
    {
        $client = static::createClient();
        $em = $this->getEm();

        $user = $this->createUser($em, 'adherent-view@example.com');
        $client->loginUser($user);
        $client->request('GET', '/adherent/propositions');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');

        $this->cleanup($em, $user);
    }

    /**
     * @group db
     */
    public function testAdherentCanCreateProposition(): void
    {
        $client = static::createClient();
        $em = $this->getEm();

        $user = $this->createUser($em, 'adherent-create@example.com');
        $userId = (int) $user->getId();

        $client->loginUser($user);

        $crawler = $client->request('GET', '/adherent/propositions');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Envoyer la proposition')->form([
            'titre'   => 'Test proposition',
            'contenu' => 'Contenu de test pour la proposition.',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/adherent/propositions');
        $client->followRedirect();
        $this->assertSelectorTextContains('.flash-success', 'transmise');

        // Recharge l'EM apres la requete client (nouveau contexte).
        $em = $this->getEm();
        $reloadedUser = $em->find(User::class, $userId);
        $this->assertNotNull($reloadedUser);

        $proposition = $em->getRepository(Proposition::class)->findOneBy([
            'auteur' => $reloadedUser,
            'titre'  => 'Test proposition',
        ]);
        $this->assertNotNull($proposition);
        $this->assertFalse($proposition->isEstLuParAdmin());

        if ($proposition !== null) {
            $em->remove($proposition);
        }
        $this->cleanup($em, $reloadedUser);
    }

    /**
     * @group db
     */
    public function testAdherentCannotDeleteOtherUsersProposition(): void
    {
        $client = static::createClient();
        $em = $this->getEm();

        $auteur = $this->createUser($em, 'auteur-403@example.com');
        $other  = $this->createUser($em, 'other-403@example.com');

        $proposition = new Proposition();
        $proposition->setAuteur($auteur);
        $proposition->setTitre('Proposition de auteur');
        $proposition->setContenu('Contenu.');
        $em->persist($proposition);
        $em->flush();

        $id = (int) $proposition->getId();

        // Connexion en tant que "other" puis premiere requete pour etablir la session.
        $client->loginUser($other);
        $client->request('GET', '/adherent/propositions');

        // Recupere le token CSRF depuis le container du client (session active).
        $csrfToken = $client->getContainer()
            ->get('security.csrf.token_manager')
            ->getToken('member_proposition_delete_' . $id)
            ->getValue();

        $client->request('POST', '/adherent/propositions/' . $id . '/supprimer', [
            '_token' => $csrfToken,
        ]);

        // Le voter doit refuser l'acces (403).
        $this->assertResponseStatusCodeSame(403);

        // Nettoyage — recharge les entites depuis la base apres les requetes client.
        $em = $this->getEm();
        $p = $em->find(Proposition::class, $id);
        if ($p !== null) {
            $em->remove($p);
        }
        $this->cleanup($em, $em->find(User::class, $auteur->getId()));
        $this->cleanup($em, $em->find(User::class, $other->getId()));
    }

    /**
     * @group db
     */
    public function testAdminSeesAllPropositions(): void
    {
        $client = static::createClient();
        $em = $this->getEm();

        $admin  = $this->createUser($em, 'admin-list@example.com', ['ROLE_ADMIN']);
        $auteur = $this->createUser($em, 'auteur-list@example.com');

        $proposition = new Proposition();
        $proposition->setAuteur($auteur);
        $proposition->setTitre('Proposition visible par admin');
        $proposition->setContenu('Contenu.');
        $em->persist($proposition);
        $em->flush();

        $propId   = (int) $proposition->getId();
        $adminId  = (int) $admin->getId();
        $auteurId = (int) $auteur->getId();

        $client->loginUser($admin);
        $client->request('GET', '/admin/propositions');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Proposition visible par admin');

        $em = $this->getEm();
        $p = $em->find(Proposition::class, $propId);
        if ($p !== null) {
            $em->remove($p);
        }
        $this->cleanup($em, $em->find(User::class, $auteurId));
        $this->cleanup($em, $em->find(User::class, $adminId));
    }

    /**
     * @group db
     */
    public function testAdminShowMarksPropositionAsRead(): void
    {
        $client = static::createClient();
        $em = $this->getEm();

        $admin  = $this->createUser($em, 'admin-read@example.com', ['ROLE_ADMIN']);
        $auteur = $this->createUser($em, 'auteur-read@example.com');

        $proposition = new Proposition();
        $proposition->setAuteur($auteur);
        $proposition->setTitre('Non lu au depart');
        $proposition->setContenu('Contenu.');
        $em->persist($proposition);
        $em->flush();

        $this->assertFalse($proposition->isEstLuParAdmin());

        $id       = (int) $proposition->getId();
        $adminId  = (int) $admin->getId();
        $auteurId = (int) $auteur->getId();

        $client->loginUser($admin);
        $client->request('GET', '/admin/propositions/' . $id);

        $this->assertResponseIsSuccessful();

        // Recharge depuis la base (nouveau contexte EM apres la requete).
        $em = $this->getEm();
        /** @var Proposition|null $reloaded */
        $reloaded = $em->find(Proposition::class, $id);
        $this->assertNotNull($reloaded);
        $this->assertTrue($reloaded->isEstLuParAdmin());

        if ($reloaded !== null) {
            $em->remove($reloaded);
        }
        $this->cleanup($em, $em->find(User::class, $auteurId));
        $this->cleanup($em, $em->find(User::class, $adminId));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @return \Doctrine\ORM\EntityManagerInterface */
    private function getEm(): object
    {
        return static::getContainer()->get('doctrine')->getManager();
    }

    /** @param list<string> $roles */
    private function createUser(object $em, string $email, array $roles = []): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPassword('hashed-not-used');
        if ($roles !== []) {
            $user->setRoles($roles);
        }
        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function cleanup(object $em, ?object $entity): void
    {
        if ($entity !== null) {
            $em->remove($entity);
            $em->flush();
        }
    }
}
