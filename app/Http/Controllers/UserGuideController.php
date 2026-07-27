<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;

final class UserGuideController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 401);

        return view('help.guide', [
            'user' => $user,
        ]);
    }
}
