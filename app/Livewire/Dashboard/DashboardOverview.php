<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class DashboardOverview extends Component
{
    public function render(): View
    {
        return view('livewire.dashboard.dashboard-overview');
    }
}
