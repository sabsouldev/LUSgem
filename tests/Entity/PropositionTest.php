<?php

namespace App\Tests\Entity;

use App\Entity\Proposition;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class PropositionTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $proposition = new Proposition();

        $this->assertSame('', $proposition->getTitre());
        $this->assertSame('', $proposition->getContenu());
        $this->assertFalse($proposition->isEstLuParAdmin());
        $this->assertNull($proposition->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $proposition->getDateCreation());
    }

    public function testDateCreationIsSetOnConstruct(): void
    {
        $before = new \DateTimeImmutable();
        $proposition = new Proposition();
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $proposition->getDateCreation());
        $this->assertLessThanOrEqual($after, $proposition->getDateCreation());
    }

    public function testSetTitre(): void
    {
        $proposition = new Proposition();
        $result = $proposition->setTitre('Mon idée');

        $this->assertSame('Mon idée', $proposition->getTitre());
        $this->assertSame($proposition, $result); // fluent interface
    }

    public function testSetContenu(): void
    {
        $proposition = new Proposition();
        $proposition->setContenu('Voici le détail de ma proposition.');

        $this->assertSame('Voici le détail de ma proposition.', $proposition->getContenu());
    }

    public function testSetEstLuParAdmin(): void
    {
        $proposition = new Proposition();

        $this->assertFalse($proposition->isEstLuParAdmin());

        $proposition->setEstLuParAdmin(true);
        $this->assertTrue($proposition->isEstLuParAdmin());

        $proposition->setEstLuParAdmin(false);
        $this->assertFalse($proposition->isEstLuParAdmin());
    }

    public function testSetAuteur(): void
    {
        $user = new User();
        $user->setEmail('adherent@test.com');

        $proposition = new Proposition();
        $result = $proposition->setAuteur($user);

        $this->assertSame($user, $proposition->getAuteur());
        $this->assertSame($proposition, $result); // fluent interface
    }
}
