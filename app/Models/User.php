<?php

namespace App\Models;

use App\Events\UserSaved;
use App\Support\Facades\Cloud;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\UrlGenerator;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail, OAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected static ?UrlGenerator $urlGenerator = null;

    public string $cloud_folder = 'Solid';
    public bool $cloudSyncFailed = false;

    protected ?FilesystemAdapter $cloud = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'nextcloud_url',
        'nextcloud_username',
        'nextcloud_password',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'saved' => UserSaved::class,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'nextcloud_password',
        'remember_token',
    ];

    public function hasCloud(): bool
    {
        return ! empty($this->nextcloud_url)
            && ! empty($this->nextcloud_username)
            && ! empty($this->nextcloud_password);
    }

    public function hasScopes(): bool
    {
        return $this->hasCloud();
    }

    public function cloud(): ?FilesystemAdapter
    {
        if (! $this->hasCloud()) {
            return null;
        }

        if (is_null($this->cloud)) {
            /** @var FilesystemAdapter|null $cloud */
            $cloud = Cloud::forUser($this);
            $this->cloud = $cloud;
        }

        return $this->cloud;
    }

    public function forgetCloud(): void
    {
        $this->cloud = null;
    }

    public function url(string $path = ''): string
    {
        if (is_null(static::$urlGenerator)) {
            static::$urlGenerator = new UrlGenerator(new RouteCollection, request());

            /** @var string|null $rootUrl */
            $rootUrl = config('app.url');
            static::$urlGenerator->forceRootUrl($rootUrl);
        }

        /** @var string $generatedUrl */
        $generatedUrl = static::$urlGenerator->to($path);

        /** @var string $result */
        $result = preg_replace('/https?\:\/\//', "$0{$this->username}.", $generatedUrl);

        return $result;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
