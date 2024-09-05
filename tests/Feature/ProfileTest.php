<?php

use App\Models\User;
use App\Support\Facades\Cloud;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/account/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/account/profile', [
            'name' => 'Test User',
            'username' => 'test',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/account/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test', $user->username);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/account/profile', [
            'name' => 'Test User',
            'username' => $user->username,
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/account/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/account/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/account/profile')
        ->delete('/account/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/account/profile');

    $this->assertNotNull($user->fresh());
});

it('initializes cloud storage', function () {
    $cloud = Cloud::fake();
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/account/profile', [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'nextcloud_url' => 'https://cloud.example.com',
            'nextcloud_username' => 'username',
            'nextcloud_password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/account/profile');

    $cloud->assertContains('/Solid/profile/card.ttl', "foaf:name \"$user->name\"");
});

it('updates cloud profile', function () {
    $cloud = Cloud::fake();
    $user = User::factory()->nextcloud()->create();
    $originalName = $user->name;

    $response = $this
        ->actingAs($user)
        ->patch('/account/profile', [
            'name' => 'Updated name',
            'username' => $user->username,
            'email' => $user->email,
            'nextcloud_url' => $user->nextcloud_url,
            'nextcloud_username' => $user->nextcloud_username,
            'nextcloud_password' => $user->nextcloud_password,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/account/profile');

    $cloud->assertContains('/Solid/profile/card.ttl', $user->url());
    $cloud->assertContains('/Solid/profile/card.ttl', 'foaf:name "Updated name"');
    $cloud->assertDoesntContain('/Solid/profile/card.ttl', "foaf:name \"{$originalName}\"");
});
