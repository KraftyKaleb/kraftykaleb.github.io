<?php
declare(strict_types=1);

namespace App\Model;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use App\Service\State\CurrentTokenProvider;
use App\Service\State\TokenRequestProcessor;
use DateTimeImmutable;

/**
 * A bearer access token. Not stored: tokens are self-contained PASETOs.
 *
 * PUT /token with {"username", "password"} is answered by the "login" firewall,
 * GET /token describes the token sent in the Authorization header.
 */
#[Put(
    uriTemplate: '/token',
    uriVariables: [],
    read: false,
    deserialize: false,
    validate: false,
    processor: TokenRequestProcessor::class,
)]
#[Get(
    uriTemplate: '/token',
    uriVariables: [],
    security: "is_granted('ROLE_ADMIN')",
    provider: CurrentTokenProvider::class,
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
