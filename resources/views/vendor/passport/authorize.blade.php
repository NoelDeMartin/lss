<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {!! __('<strong>:client</strong> is requesting permission to access your account.', ['client' => $client->name]) !!}
    </div>

    @if (count($scopes) > 0)
        <div class="mb-4 text-sm text-gray-600 prose">
            <p><strong>{{ __('This application will be able to:') }}</strong></p>

            <ul>
                @foreach ($scopes as $scope)
                    <li>{{ $scope->description }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="post" action="{{ route('passport.authorizations.approve') }}">
            @csrf

            <input type="hidden" name="state" value="{{ $request->state }}">
            <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">

            <x-primary-button>
                {{ __('Authorize') }}
            </x-primary-button>
        </form>

        <form method="post" action="{{ route('passport.authorizations.deny') }}">
            @csrf
            @method('DELETE')

            <input type="hidden" name="state" value="{{ $request->state }}">
            <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">

            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                {{ __('Cancel') }}
            </button>
        </form>
    </div>
</x-guest-layout>
