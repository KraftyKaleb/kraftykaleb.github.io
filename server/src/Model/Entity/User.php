<?php
declare(strict_types=1);

namespace App\Model\Entity;


use App\Service\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User {
    #[ORM\Id]
    #[ORM\Column(
        length: 36,
        options: [
            'fixed'=> true,
        ]
    )]
    #[ORM\GeneratedValue(strategy: "NONE")]
    private string $id;
}