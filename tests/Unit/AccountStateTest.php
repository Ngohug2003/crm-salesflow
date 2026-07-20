<?php

use App\Models\User;

it('casts account state to boolean', function (): void {
    $user = new User(['is_active' => 1]);

    expect($user->is_active)->toBeTrue();
});
