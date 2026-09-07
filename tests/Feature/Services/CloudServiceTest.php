<?php

use App\Exceptions\UnsafeUrlException;
use App\Models\User;
use App\Support\CloudClient;
use App\Support\DnsResolver;
use App\Support\Facades\Cloud;
use App\Support\PublicUrl;
use Illuminate\Filesystem\FilesystemAdapter;

beforeEach(function () {
    $this->dns = ['*' => ['93.184.216.34']];

    $this->mock(DnsResolver::class)
        ->shouldReceive('resolve')
        ->andReturnUsing(fn (string $host) => $this->dns[$host] ?? $this->dns['*']);
});

it('connects to public hosts', function () {
    $user = User::factory()->nextcloud()->make(['nextcloud_url' => 'https://cloud.example.com']);

    expect(Cloud::forUser($user))->toBeInstanceOf(FilesystemAdapter::class);
});

it('refuses to connect to private addresses', function () {
    $user = User::factory()->nextcloud()->make(['nextcloud_url' => 'http://127.0.0.1:8080']);

    expect(fn () => Cloud::forUser($user))->toThrow(UnsafeUrlException::class);
});

it('refuses to connect to hostnames that resolve to private addresses', function () {
    $this->dns['cloud.example.com'] = ['10.0.0.5'];
    $user = User::factory()->nextcloud()->make(['nextcloud_url' => 'https://cloud.example.com']);

    expect(fn () => Cloud::forUser($user))->toThrow(UnsafeUrlException::class);
});

it('pins connections to the resolved addresses without following redirects', function () {
    $this->dns['cloud.example.com'] = ['93.184.216.34', '2606:2800:21f:cb07:6820:80da:af6b:8b2c'];

    $client = new CloudClient(PublicUrl::resolve('https://cloud.example.com/nextcloud'), 'username', 'password');
    [$curlSettings, $maxRedirects] = (fn () => [$this->curlSettings, $this->maxRedirects])->call($client);

    expect($curlSettings[CURLOPT_RESOLVE])
        ->toBe(['cloud.example.com:443:93.184.216.34,[2606:2800:21f:cb07:6820:80da:af6b:8b2c]'])
        ->and($maxRedirects)->toBe(0);
});

it('does not resolve nor pin hosts when private hosts are allowed', function () {
    config()->set('cloud.allow_private_hosts', true);
    $this->dns['localhost'] = [];

    $client = new CloudClient(PublicUrl::resolve('http://localhost:8080'), 'username', 'password');
    $curlSettings = (fn () => $this->curlSettings)->call($client);

    expect($curlSettings)->not->toHaveKey(CURLOPT_RESOLVE);
});

it('uses the port from the url when pinning addresses', function () {
    $url = PublicUrl::resolve('http://cloud.example.com:8080/nextcloud');

    expect($url->pinnedAddresses())->toBe('cloud.example.com:8080:93.184.216.34');
});
