<?php
declare(strict_types=1);

namespace App\Service\Security\Authentication;

use App\Model\Entity\User;
use DateInterval;
use DateTimeImmutable;
use ParagonIE\Paseto\Builder;
use ParagonIE\Paseto\Exception\PasetoException;
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

    /**
     * @return array{token: string, expires_at: DateTimeImmutable}
     */
    public function issue(User $user): array {
        $now = new DateTimeImmutable();
        $expiresAt = $now->add(new DateInterval($this->ttl));

        $token = Builder::getLocal($this->key)
            ->setIssuer(self::ISSUER)
            ->setIssuedAt($now)
            ->setNotBefore($now)
            ->setExpiration($expiresAt)
            ->setSubject($user->id->toRfc4122())
            ->toString();

        return ['token' => $token, 'expires_at' => $expiresAt];
    }

    /**
     * Returns the user id the token was issued for, or null when the token is invalid or expired.
     */
    public function verify(string $token): ?string {
        try {
            return Parser::getLocal($this->key, ProtocolCollection::v4())
                ->addRule(new IssuedBy(self::ISSUER))
                ->addRule(new ValidAt())
                ->parse($token)
                ->getSubject();
        } catch (PasetoException) {
            return null;
        }
    }
}
