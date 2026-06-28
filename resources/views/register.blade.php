<x-layout>
    <x-slot:title>
        Register
    </x-slot:title>

    <main class="min-h-screen bg-canvas px-4 py-4 font-sans text-ink sm:px-6 sm:py-6 lg:px-8">
        <div class="mx-auto flex min-h-[calc(100vh-2rem)] w-full max-w-6xl items-center sm:min-h-[calc(100vh-3rem)]">
            <div
                class="grid w-full overflow-hidden rounded-lg border border-hairline bg-white lg:min-h-190 lg:grid-cols-[1fr_0.95fr]">
                <section class="flex items-center justify-center p-5 sm:p-8 lg:order-1 lg:p-10">
                    <div class="w-full max-w-md">
                        <a href="{{ url('/') }}" wire:navigate
                            class="mb-10 flex w-fit items-center gap-3 font-semibold tracking-tight text-ink lg:hidden">
                            <img src="{{ asset('scrumlab-icon.svg') }}" alt="" class="size-10 shrink-0">
                            <span class="font-display tracking-tight">{{ config('app.name') }}</span>
                        </a>

                        <div class="mb-8">
                            <p class="deck-label text-accent">Get started</p>
                            <h1 class="mt-3 font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Create your
                                ScrumLab account</h1>
                        </div>

                        @if ($errors->any())
                            <div class="mb-6 rounded-md border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                                Please fix the highlighted fields and try again.
                            </div>
                        @endif

                        <form action="{{ route('register.store') }}" method="POST" class="space-y-5"
                            id="register-form">
                            @csrf

                            <div>
                                <label for="name" class="block text-sm font-semibold text-ink">Full
                                    name</label>
                                <input type="text" id="name" name="name" value="{{ old('name') }}"
                                    autocomplete="name" required @class([
                                        'mt-2 block w-full rounded-md border bg-white px-3.5 py-3 text-sm text-ink outline-none transition placeholder:text-neutral-400 focus:border-accent focus:ring-2 focus:ring-accent/20',
                                        'border-rose-300' => $errors->has('name'),
                                        'border-hairline' => !$errors->has('name'),
                                    ])>
                                @error('name')
                                    <p class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-semibold text-ink">Email
                                    address</label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}"
                                    autocomplete="email" required @class([
                                        'mt-2 block w-full rounded-md border bg-white px-3.5 py-3 text-sm text-ink outline-none transition placeholder:text-neutral-400 focus:border-accent focus:ring-2 focus:ring-accent/20',
                                        'border-rose-300' => $errors->has('email'),
                                        'border-hairline' => !$errors->has('email'),
                                    ])>
                                @error('email')
                                    <p class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="password"
                                        class="block text-sm font-semibold text-ink">Password</label>
                                    <div class="relative mt-2">
                                        <input type="password" id="password" name="password"
                                            autocomplete="new-password" required @class([
                                                'block w-full rounded-md border bg-white px-3.5 py-3 pr-12 text-sm text-ink outline-none transition placeholder:text-neutral-400 focus:border-accent focus:ring-2 focus:ring-accent/20',
                                                'border-rose-300' => $errors->has('password'),
                                                'border-hairline' => !$errors->has('password'),
                                            ])>
                                        <button type="button" data-password-toggle="password"
                                            aria-label="Show password"
                                            class="absolute inset-y-0 right-0 grid w-11 place-items-center text-neutral-500 transition hover:text-ink">
                                            <svg class="size-5" data-eye-icon xmlns="http://www.w3.org/2000/svg"
                                                fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                                                stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                            <svg class="hidden size-5" data-eye-off-icon
                                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M10.73 5.08A10.73 10.73 0 0 1 12 5c6 0 9.75 7 9.75 7a17.93 17.93 0 0 1-3.33 4.24" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6.61 6.61C3.8 8.5 2.25 12 2.25 12S6 19 12 19a10.7 10.7 0 0 0 4.08-.8" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9.88 9.88A3 3 0 0 0 14.12 14.12" />
                                            </svg>
                                            <span class="sr-only">Show password</span>
                                        </button>
                                    </div>
                                    @error('password')
                                        <p class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="password_confirmation"
                                        class="block text-sm font-semibold text-ink">Confirm</label>
                                    <div class="relative mt-2">
                                        <input type="password" id="password_confirmation" name="password_confirmation"
                                            autocomplete="new-password" required @class([
                                                'block w-full rounded-md border bg-white px-3.5 py-3 pr-12 text-sm text-ink outline-none transition placeholder:text-neutral-400 focus:border-accent focus:ring-2 focus:ring-accent/20',
                                                'border-rose-300' => $errors->has('password'),
                                                'border-hairline' => !$errors->has('password'),
                                            ])>
                                        <button type="button" data-password-toggle="password_confirmation"
                                            aria-label="Show confirm password"
                                            class="absolute inset-y-0 right-0 grid w-11 place-items-center text-neutral-500 transition hover:text-ink">
                                            <svg class="size-5" data-eye-icon xmlns="http://www.w3.org/2000/svg"
                                                fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                                                stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                            <svg class="hidden size-5" data-eye-off-icon
                                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M10.73 5.08A10.73 10.73 0 0 1 12 5c6 0 9.75 7 9.75 7a17.93 17.93 0 0 1-3.33 4.24" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6.61 6.61C3.8 8.5 2.25 12 2.25 12S6 19 12 19a10.7 10.7 0 0 0 4.08-.8" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9.88 9.88A3 3 0 0 0 14.12 14.12" />
                                            </svg>
                                            <span class="sr-only">Show confirm password</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <p id="password-match-error" class="hidden text-sm text-rose-700">
                                Passwords do not match.
                            </p>

                            <label class="flex items-start gap-3 text-sm leading-6 text-neutral-500" for="terms">
                                <input id="terms" name="terms" type="checkbox" required
                                    {{ old('terms') ? 'checked' : '' }}
                                    class="mt-1 size-4 rounded border-hairline text-accent focus:ring-accent">
                                <span>I agree to create a ScrumLab workspace profile for project planning and
                                    collaboration.</span>
                            </label>
                            @error('terms')
                                <p class="text-sm text-rose-700">{{ $message }}</p>
                            @enderror

                            <button type="submit"
                                class="flex w-full justify-center rounded-md bg-accent px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-strong focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2">
                                Create account
                            </button>
                        </form>

                        <p class="mt-8 text-center text-sm text-neutral-500">
                            Already have an account?
                            <a href="{{ route('login') }}" wire:navigate
                                class="font-semibold text-accent transition hover:text-accent-strong">Log
                                in</a>
                        </p>
                    </div>
                </section>

                <section
                    class="order-first hidden flex-col gap-8 border-b border-hairline bg-canvas p-5 sm:p-8 lg:order-0 lg:flex lg:border-b-0 lg:border-r lg:p-10">
                    <a href="{{ url('/') }}" wire:navigate
                        class="hidden w-fit items-center gap-3 font-semibold tracking-tight text-ink lg:flex">
                        <img src="{{ asset('scrumlab-icon.svg') }}" alt="" class="size-10 shrink-0">
                        <span class="font-display tracking-tight">{{ config('app.name') }}</span>
                    </a>

                    <div class="max-w-xl lg:my-auto">
                        <h2
                            class="font-display text-3xl font-bold leading-tight tracking-tight text-ink min-[420px]:text-4xl sm:text-5xl">
                            Create a place for projects, sprints, and issues.
                        </h2>
                    </div>

                    <div class="grid gap-3 min-[520px]:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
                        <div class="rounded-lg border border-hairline bg-white p-4">
                            <p class="deck-label text-neutral-400">Projects</p>
                            <p class="mt-3 font-display text-2xl font-bold tabular-nums text-ink">3</p>
                        </div>
                        <div class="rounded-lg border border-hairline bg-white p-4">
                            <p class="deck-label text-neutral-400">Sprint stages</p>
                            <p class="mt-3 font-display text-2xl font-bold tabular-nums text-ink">5</p>
                        </div>
                        <div class="rounded-lg border border-hairline bg-white p-4">
                            <p class="deck-label text-neutral-400">Issue types</p>
                            <p class="mt-3 font-display text-2xl font-bold tabular-nums text-ink">4</p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script>
        document.querySelectorAll('[data-password-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.passwordToggle);
                const shouldShow = input.type === 'password';

                input.type = shouldShow ? 'text' : 'password';
                button.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
                button.querySelector('.sr-only').textContent = shouldShow ? 'Hide password' :
                    'Show password';
                button.querySelector('[data-eye-icon]').classList.toggle('hidden', shouldShow);
                button.querySelector('[data-eye-off-icon]').classList.toggle('hidden', !shouldShow);
            });
        });
        const registerForm = document.getElementById('register-form');
        const passwordMatchError = document.getElementById('password-match-error');

        if (registerForm && passwordMatchError) {
            registerForm.addEventListener('submit', (e) => {
                const formData = new FormData(e.target);
                const password = formData.get('password');
                const confirmation = formData.get('password_confirmation');

                if (password !== confirmation) {
                    e.preventDefault();
                    passwordMatchError.classList.remove('hidden');
                    return;
                }

                passwordMatchError.classList.add('hidden');
            });
        }
    </script>
</x-layout>
