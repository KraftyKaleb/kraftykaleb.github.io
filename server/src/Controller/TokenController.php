<?php
declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TokenController {
    /**
     * The "login" firewall's json_login handles JSON requests to this route before it is reached;
     * the controller only answers requests that were not sent as JSON.
     */
    #[Route('/token', name: 'app_token', methods: ['PUT'])]
    public function __invoke(): JsonResponse {
        return new JsonResponse(
            ['error' => 'Send a JSON body with "username" and "password".'],
            Response::HTTP_BAD_REQUEST,
        );
    }
}
