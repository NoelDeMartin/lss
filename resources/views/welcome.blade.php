<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>LSS</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="font-sans antialiased flex flex-col min-h-full bg-gray-100">
        <main class="flex flex-col flex-grow items-center justify-center">
            <div class="text-4xl flex items-center">
                <a href="https://laravel.com" target="_blank" class="mr-4">
                    <x-icons.laravel class="size-32" />
                </a>
                <span class="mr-2">+</span>
                <a href="https://solidproject.org" target="_blank" class="mr-2">
                    <x-icons.solid class="size-40" />
                </a>
                <span class="mr-4">=</span>
                <x-icons.love class="size-32" />
            </div>
            <div class="mt-10">
                <a
                    href="{{ route('login') }}"
                    class="rounded-md bg-[#6437e3] px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#7c4dff] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#6437e3]"
                >
                    {{ __('Log In') }}
                </a>
            </div>
        </main>
        <footer class="flex items-center justify-center p-4">
            <a href="https://github.com/noeldemartin/lss" class="text-xs text-gray-700 hover:underline">
                {{ __('What is this?') }}
            </a>
        </footer>
    </body>
</html>
