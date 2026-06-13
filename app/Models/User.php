<?php

namespace App\Models;

use App\Events\UserSaved;
use App\Support\Facades\Cloud;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\UrlGenerator;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected static $urlGenerator = null;

    public $cloud_folder = 'Solid';
    public $cloudSyncFailed = false;

    protected $cloud = null;

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
            $this->cloud = Cloud::forUser($this);
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

            static::$urlGenerator->forceRootUrl(config('app.url'));
        }

        return preg_replace('/https?\:\/\//', "$0{$this->username}.", static::$urlGenerator->to($path));
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
