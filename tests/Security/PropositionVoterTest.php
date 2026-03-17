<?php

namespace App\Tests\Security;

use App\Entity\Proposition;
use App\Entity\User;
use App\Security\PropositionVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class PropositionVoterTest extends TestCase
{
    private PropositionVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new PropositionVoter();
    }

    public function testOwnerCanDelete(): void
    {
        $user = new User();
        $user->setEmail('auteur@test.com');

        $proposition = new Proposition();
        $proposition->setAuteur($user);

        $token = $this->buildToken($user);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $proposition, [PropositionVoter::DELETE])
        );
    }

    public function testNonOwnerCannotDelete(): void
    {
        $auteur = new User();
        $auteur->setEmail('auteur@test.com');

        $other = new User();
        $other->setEmail('other@test.com');

        $proposition = new Proposition();
        $proposition->setAuteur($auteur);

        $token = $this->buildToken($other);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $proposition, [PropositionVoter::DELETE])
        );
    }

    public function testUnauthenticatedUserCannotDelete(): void
    {
        $auteur = new User();
        $auteur->setEmail('auteur@test.com');

        $proposition = new Proposition();
        $proposition->setAuteur($auteur);

        $token = $this->buildToken(null);

        // Voter abstains (not granted) when no user is authenticated.
        $this->assertNotSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $proposition, [PropositionVoter::DELETE])
        );
    }

    public function testVoterAbstainsOnUnknownAttribute(): void
    {
        $user = new User();
        $user->setEmail('user@test.com');

        $proposition = new Proposition();
        $proposition->setAuteur($user);

        $token = $this->buildToken($user);

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $proposition, ['UNKNOWN_ATTRIBUTE'])
        );
    }

    public function testVoterAbstainsOnNonPropositionSubject(): void
    {
        $user = new User();
        $token = $this->buildToken($user);

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, new \stdClass(), [PropositionVoter::DELETE])
        );
    }

    private function buildToken(?User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }
}
