<?php

use App\Models\User;

test('appearance page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings/appearance')
        ->assertOk();
});
