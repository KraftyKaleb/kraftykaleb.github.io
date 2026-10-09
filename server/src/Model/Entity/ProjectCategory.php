<?php
declare(strict_types=1);

namespace App\Model\Entity;

/** The section of the projects page a project is listed under. */
enum ProjectCategory: string {
    case Professional = 'professional';
    case Side = 'side';
    case Other = 'other';
}
