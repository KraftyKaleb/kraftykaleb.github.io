<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class MeController {
    #[Route('/admin/me', name: 'app_admin_me', methods: ['GET'])]
    public function __invoke(#[CurrentUser] User $user): JsonResponse {
        return new JsonResponse([
            'id' => $user->id->toRfc4122(),
            'username' => $user->username,
            'roles' => $user->getRoles(),
        ]);
    }
}
