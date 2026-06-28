<x-layout>
    <x-slot:title>
        Forgot Password
    </x-slot:title>

    <main class="min-h-screen bg-canvas px-4 py-4 font-sans text-ink sm:px-6 sm:py-6 lg:px-8">
        <div class="mx-auto flex min-h-[calc(100vh-2rem)] w-full max-w-2xl items-center sm:min-h-[calc(100vh-3rem)]">
            <section class="w-full rounded-lg border border-hairline bg-white p-5 sm:p-8">
                <a href="{{ url('/') }}" wire:navigate
                    class="mb-10 flex w-fit items-center gap-3 font-semibold tracking-tight text-ink">
                    <img src="{{ asset('scrumlab-icon.svg') }}" alt="" class="size-10 shrink-0">
                    <span class="font-display tracking-tight">{{ config('app.name') }}</span>
                </a>

                <div class="mb-8">
                    <p class="deck-label text-accent">Account recovery</p>
                    <h1 class="mt-3 font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Reset your password</h1>
                </div>

                @if (session('status'))
                    <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                <form action="{{ route('password.email') }}" method="POST" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-semibold text-ink">Email address</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="email" required
                            @class([
                                'mt-2 block w-full rounded-md border bg-white px-3.5 py-3 text-sm text-ink outline-none transition placeholder:text-neutral-400 focus:border-accent focus:ring-2 focus:ring-accent/20',
                                'border-rose-300' => $errors->has('email'),
                                'border-hairline' => ! $errors->has('email'),
                            ])>
                        @error('email')
                            <p class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                        class="flex w-full justify-center rounded-md bg-accent px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-strong focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2">
                        Email reset link
                    </button>
                </form>

                <p class="mt-8 text-center text-sm text-neutral-500">
                    Remembered it?
                    <a href="{{ route('login') }}" wire:navigate
                        class="font-semibold text-accent transition hover:text-accent-strong">Back to login</a>
                </p>
            </section>
        </div>
    </main>
</x-layout>
