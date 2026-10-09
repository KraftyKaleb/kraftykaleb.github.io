<?php
declare(strict_types=1);

namespace App\Model;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use App\Service\State\Processor\TokenPutProcessor;
use App\Service\State\Provider\TokenGetProvider;
use DateTimeImmutable;

/**
 * A bearer access token. Not stored: tokens are self-contained PASETOs.
 *
 * PUT /api/token with {"username", "password"} is answered by the "login" firewall,
 * GET /api/token describes the token sent in the Authorization header.
 */
#[Put(
    uriTemplate: '/token',
    uriVariables: [],
    read: false,
    deserialize: false,
    validate: false,
    processor: TokenPutProcessor::class,
)]
#[Get(
    uriTemplate: '/token',
    uriVariables: [],
    security: "is_granted('ROLE_ADMIN')",
    provider: TokenGetProvider::class,
)]
final class Token {
    public function __construct(
        #[ApiProperty(identifier: true)]
        public private(set) string $token,
        public private(set) DateTimeImmutable $expiresAt,
        public private(set) string $username,
    ) {
    }
}
