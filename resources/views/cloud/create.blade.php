<x-guest-layout>
    <div class="font-medium text-sm text-green-600">
        {{ __('You need to configure your Nextcloud account') }}
    </div>

    <form method="POST" action="{{ route('cloud.store') }}">
        @csrf

        <div>
            <x-input-label for="nextcloud_url" :value="__('Nextcloud URL')" />
            <x-text-input id="nextcloud_url" name="nextcloud_url" type="text" class="mt-1 block w-full" />
            <x-input-error class="mt-2" :messages="$errors->get('nextcloud_url')" />
        </div>

        <div>
            <x-input-label for="nextcloud_username" :value="__('Nextcloud Username')" />
            <x-text-input id="nextcloud_username" name="nextcloud_username" type="text" class="mt-1 block w-full" />
            <x-input-error class="mt-2" :messages="$errors->get('nextcloud_username')" />
        </div>

        <div>
            <x-input-label for="nextcloud_password" :value="__('Nextcloud Password')" />
            <x-text-input id="nextcloud_password" name="nextcloud_password" type="password" class="mt-1 block w-full" />
            <x-input-error class="mt-2" :messages="$errors->get('nextcloud_password')" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ms-3">
                {{ __('Setup Cloud') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
