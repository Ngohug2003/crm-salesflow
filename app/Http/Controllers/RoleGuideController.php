<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\RoleGuideService;
use Illuminate\Contracts\View\View;

final class RoleGuideController extends Controller
{
    public function __invoke(RoleGuideService $roleGuide): View
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 401);

        return view('help.roles', [
            'currentUserSummary' => $roleGuide->currentUserSummary($user),
            'roleCatalog' => $roleGuide->catalog(),
        ]);
    }
}
