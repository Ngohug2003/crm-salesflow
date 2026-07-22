<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

final class DepartmentController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Department::class);

        return view('departments.index');
    }
}
