<x-layout>
    <x-slot:title>
        Reset Password
    </x-slot:title>

    <main class="min-h-screen bg-stone-50 px-4 py-4 font-sans text-neutral-950 sm:px-6 sm:py-6 lg:px-8">
        <div class="mx-auto flex min-h-[calc(100vh-2rem)] w-full max-w-2xl items-center sm:min-h-[calc(100vh-3rem)]">
            <section class="w-full rounded-lg border border-neutral-200 bg-white p-5 shadow-sm sm:p-8">
                <a href="{{ url('/') }}" wire:navigate
                    class="mb-10 flex w-fit items-center gap-3 font-semibold tracking-tight text-neutral-950">
                    <img src="{{ asset('scrumlab-icon.svg') }}" alt="" class="size-10 shrink-0">
                    <span>{{ config('app.name') }}</span>
                </a>

                <div class="mb-8">
                    <p class="text-sm font-medium text-neutral-500">Choose new credentials</p>
                    <h1 class="mt-2 text-2xl font-bold tracking-normal text-neutral-950 sm:text-3xl">Set a new password</h1>
                    <p class="mt-3 text-sm leading-6 text-neutral-500">
                        Use a password with at least 8 characters.
                    </p>
                </div>

                <form action="{{ route('password.update') }}" method="POST" class="space-y-5">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div>
                        <label for="email" class="block text-sm font-semibold text-neutral-800">Email address</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" required
                            @class([
                                'mt-2 block w-full rounded-md border bg-white px-3.5 py-3 text-sm text-neutral-950 shadow-xs outline-none transition placeholder:text-neutral-400 focus:border-neutral-950 focus:ring-2 focus:ring-neutral-950/10',
                                'border-rose-300' => $errors->has('email'),
                                'border-neutral-300' => ! $errors->has('email'),
                            ])>
                        @error('email')
                            <p class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-semibold text-neutral-800">New password</label>
                        <input type="password" id="password" name="password" autocomplete="new-password" required
                            @class([
                                'mt-2 block w-full rounded-md border bg-white px-3.5 py-3 text-sm text-neutral-950 shadow-xs outline-none transition placeholder:text-neutral-400 focus:border-neutral-950 focus:ring-2 focus:ring-neutral-950/10',
                                'border-rose-300' => $errors->has('password'),
                                'border-neutral-300' => ! $errors->has('password'),
                            ])>
                        @error('password')
                            <p class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-neutral-800">Confirm password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required
                            class="mt-2 block w-full rounded-md border border-neutral-300 bg-white px-3.5 py-3 text-sm text-neutral-950 shadow-xs outline-none transition placeholder:text-neutral-400 focus:border-neutral-950 focus:ring-2 focus:ring-neutral-950/10">
                    </div>

                    <button type="submit"
                        class="flex w-full justify-center rounded-md border border-neutral-950 bg-neutral-950 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-neutral-800 focus:outline-none focus:ring-2 focus:ring-neutral-950 focus:ring-offset-2">
                        Reset password
                    </button>
                </form>
            </section>
        </div>
    </main>
</x-layout>
