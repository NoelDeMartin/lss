<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('nextcloud_password')->nullable()->change();
        });

        $users = DB::table('users')
            ->whereNotNull('nextcloud_password')
            ->select('id', 'nextcloud_password')
            ->lazyById();

        foreach ($users as $user) {
            try {
                Crypt::decryptString($user->nextcloud_password);
            } catch (DecryptException) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'nextcloud_password' => Crypt::encryptString($user->nextcloud_password),
                    ]);
            }
        }
    }

    public function down(): void
    {
        $users = DB::table('users')
            ->whereNotNull('nextcloud_password')
            ->select('id', 'nextcloud_password')
            ->lazyById();

        foreach ($users as $user) {
            try {
                $decrypted = Crypt::decryptString($user->nextcloud_password);

                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'nextcloud_password' => $decrypted,
                    ]);
            } catch (DecryptException) {
                // Already plaintext, nothing to do
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('nextcloud_password')->nullable()->change();
        });
    }
};
