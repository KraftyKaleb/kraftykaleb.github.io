<?php
declare(strict_types=1);

namespace App\Service\Security\Authentication;

use App\Model\Entity\User;
use App\Model\Token;
use DateInterval;
use DateTimeImmutable;
use ParagonIE\Paseto\Builder;
use ParagonIE\Paseto\Exception\PasetoException;
use ParagonIE\Paseto\JsonToken;
use ParagonIE\Paseto\Keys\Base\SymmetricKey;
use ParagonIE\Paseto\Parser;
use ParagonIE\Paseto\ProtocolCollection;
use ParagonIE\Paseto\Rules\IssuedBy;
use ParagonIE\Paseto\Rules\ValidAt;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Issues and verifies the PASETO (v4.local) access tokens handed out by PUT /token.
 */
final class TokenService {
    private const string ISSUER = 'kraftykaleb-admin-api';
    private const string USERNAME_CLAIM = 'username';

    private readonly SymmetricKey $key;

    public function __construct(
        #[Autowire('%kernel.secret%')]
        string $secret,
        #[Autowire('%app.access_token_ttl%')]
        private readonly string $ttl,
    ) {
        if ($secret === '') {
            throw new \LogicException('APP_SECRET must be set to issue access tokens.');
        }

        $this->key = SymmetricKey::v4(hash_hkdf('sha256', $secret, 32, 'access-token'));
    }

    public function issue(User $user): Token {
        $now = new DateTimeImmutable();
        $expiresAt = $now->add(new DateInterval($this->ttl));

        $token = Builder::getLocal($this->key)
            ->setIssuer(self::ISSUER)
            ->setIssuedAt($now)
            ->setNotBefore($now)
            ->setExpiration($expiresAt)
            ->setSubject($user->id)
            ->set(self::USERNAME_CLAIM, $user->username)
            ->toString();

        return new Token($token, $expiresAt, $user->username);
    }

    /**
     * Returns the id of the user the token was issued for, or null when the token is invalid or expired.
     */
    public function verify(string $token): ?string {
        return $this->parse($token)?->getSubject();
    }

    /**
     * Returns the token's details, or null when the token is invalid or expired.
     */
    public function read(string $token): ?Token {
        $parsed = $this->parse($token);

        return $parsed === null ? null : new Token(
            $token,
            DateTimeImmutable::createFromInterface($parsed->getExpiration()),
            $parsed->get(self::USERNAME_CLAIM),
        );
    }

    private function parse(string $token): ?JsonToken {
        try {
            return Parser::getLocal($this->key, ProtocolCollection::v4())
                ->addRule(new IssuedBy(self::ISSUER))
                ->addRule(new ValidAt())
                ->parse($token);
        } catch (PasetoException) {
            return null;
        }
    }
}
