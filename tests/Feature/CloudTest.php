<?php

use App\Models\User;
use App\Support\DnsResolver;
use App\Support\Facades\Cloud;

beforeEach(function () {
    $this->dns = ['*' => ['93.184.216.34']];

    $this->mock(DnsResolver::class)
        ->shouldReceive('resolve')
        ->andReturnUsing(fn (string $host) => $this->dns[$host] ?? $this->dns['*']);
});

it('accepts public hosts on any port', function (string $url) {
    Cloud::fake();
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/account/profile')
        ->put('/cloud', [
            'nextcloud_url' => $url,
            'nextcloud_username' => 'username',
            'nextcloud_password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/account/profile');

    expect($user->fresh()?->nextcloud_url)->toBe($url);
})->with([
    'https://cloud.example.com',
    'https://cloud.example.com:8443/nextcloud',
    'http://93.184.216.34/',
]);

it('rejects malformed urls and unsupported schemes', function (string $url) {
    Cloud::fake();
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/account/profile')
        ->put('/cloud', [
            'nextcloud_url' => $url,
            'nextcloud_username' => 'username',
            'nextcloud_password' => 'password',
        ]);

    $response
        ->assertSessionHasErrors('nextcloud_url')
        ->assertRedirect('/account/profile');

    expect($user->fresh()?->nextcloud_url)->toBeNull();
})->with([
    'ftp://example.com/',
    'file:///etc/passwd',
    'gopher://example.com',
    'example.com/no-scheme',
    'not a url',
    'http://',
]);

it('rejects private, loopback, link-local and reserved addresses', function (string $url) {
    Cloud::fake();
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/account/profile')
        ->put('/cloud', [
            'nextcloud_url' => $url,
            'nextcloud_username' => 'username',
            'nextcloud_password' => 'password',
        ]);

    $response
        ->assertSessionHasErrors('nextcloud_url')
        ->assertRedirect('/account/profile');

    expect($user->fresh()?->nextcloud_url)->toBeNull();
})->with([
    'http://127.0.0.1/',
    'http://127.1.2.3:8080/',
    'http://0.0.0.0/',
    'http://10.0.0.1/',
    'http://172.17.0.2/',
    'http://192.168.1.1/',
    'http://169.254.169.254/latest/meta-data/',
    'http://100.64.0.1/',
    'http://[::1]/',
    'http://[::]/',
    'http://[fe80::1]/',
    'http://[fd00::1]/',
    'http://[::ffff:127.0.0.1]/',
]);

it('accepts private hosts when explicitly allowed', function () {
    config()->set('cloud.allow_private_hosts', true);
    Cloud::fake();
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/account/profile')
        ->put('/cloud', [
            'nextcloud_url' => 'http://localhost:8080',
            'nextcloud_username' => 'username',
            'nextcloud_password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/account/profile');

    expect($user->fresh()?->nextcloud_url)->toBe('http://localhost:8080');
});

it('rejects hostnames that resolve to private addresses', function (array $ips) {
    $this->dns['internal.example.com'] = $ips;
    Cloud::fake();
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/account/profile')
        ->put('/cloud', [
            'nextcloud_url' => 'https://internal.example.com',
            'nextcloud_username' => 'username',
            'nextcloud_password' => 'password',
        ]);

    $response
        ->assertSessionHasErrors('nextcloud_url')
        ->assertRedirect('/account/profile');

    expect($user->fresh()?->nextcloud_url)->toBeNull();
})->with([
    'ipv4' => [['172.18.0.5']],
    'ipv6' => [['fd00::5']],
    'public and private' => [['93.184.216.34', '10.0.0.5']],
    'public ipv4 and loopback ipv6' => [['93.184.216.34', '::1']],
]);

it('rejects hostnames that cannot be resolved', function () {
    $this->dns['missing.example.com'] = [];
    Cloud::fake();
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/account/profile')
        ->put('/cloud', [
            'nextcloud_url' => 'https://missing.example.com',
            'nextcloud_username' => 'username',
            'nextcloud_password' => 'password',
        ]);

    $response
        ->assertSessionHasErrors('nextcloud_url')
        ->assertRedirect('/account/profile');

    expect($user->fresh()?->nextcloud_url)->toBeNull();
});

it('validates the url when setting up the cloud for the first time', function () {
    Cloud::fake();
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/cloud/create')
        ->post('/cloud', [
            'nextcloud_url' => 'http://192.168.1.10/',
            'nextcloud_username' => 'username',
            'nextcloud_password' => 'password',
        ]);

    $response
        ->assertSessionHasErrors('nextcloud_url')
        ->assertRedirect('/cloud/create');

    expect($user->fresh()?->nextcloud_url)->toBeNull();
});
