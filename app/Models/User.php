<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use League\Flysystem\Filesystem as LeagueFilesystem;
use League\Flysystem\WebDAV\WebDAVAdapter;
use Sabre\DAV\Client;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
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
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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

    public function cloud(): ?Filesystem
    {
        if (! $this->hasCloud()) {
            return null;
        }

        $folder = 'Solid';
        $config = [
            'baseUri' => $this->nextcloud_url,
            'userName' => $this->nextcloud_username,
            'password' => $this->nextcloud_password,
            'throw' => app()->hasDebugModeEnabled(),
        ];
        $client = new Client($config);
        $adapter = new WebDAVAdapter($client, "remote.php/dav/files/{$this->nextcloud_username}/$folder/");

        return new FilesystemAdapter(
            new LeagueFilesystem($adapter, $config),
            $adapter,
            $config
        );
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
