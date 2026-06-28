<x-layout>
    <x-slot:title>
        Login
    </x-slot:title>

    <main class="min-h-screen bg-canvas px-4 py-4 font-sans text-ink sm:px-6 sm:py-6 lg:px-8">
        <div class="mx-auto flex min-h-[calc(100vh-2rem)] w-full max-w-6xl items-center sm:min-h-[calc(100vh-3rem)]">
            <div
                class="grid w-full overflow-hidden rounded-lg border border-hairline bg-white lg:min-h-180 lg:grid-cols-[1fr_0.95fr]">
                <section
                    class="hidden flex-col gap-8 border-b border-hairline bg-canvas p-5 sm:p-8 lg:flex lg:border-b-0 lg:border-r lg:p-10">
                    <a href="{{ url('/') }}" wire:navigate
                        class="flex w-fit items-center gap-3 font-semibold tracking-tight text-ink">
                        <img src="{{ asset('scrumlab-icon.svg') }}" alt="" class="size-10 shrink-0">
                        <span class="font-display tracking-tight">{{ config('app.name') }}</span>
                    </a>

                    <div class="max-w-xl lg:my-auto">
                        <p class="deck-label text-accent">Welcome back</p>
                        <h1
                            class="mt-4 font-display text-3xl font-bold leading-tight tracking-tight text-ink min-[420px]:text-4xl sm:text-5xl">
                            Pick up your sprint exactly where the team left it.
                        </h1>
                        <p class="mt-5 max-w-lg text-sm leading-6 text-neutral-500 sm:text-base sm:leading-7">
                            Sign in to manage projects, review sprint boards, update issues, and keep team progress
                            visible.
                        </p>
                    </div>

                    <div class="rounded-lg border border-hairline bg-white p-4">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div>
                                <p class="font-display text-sm font-semibold tracking-tight text-ink">Sprint board</p>
                                <p class="font-mono text-xs text-neutral-400">Campus Portal &middot; Sprint 02</p>
                            </div>
                            <span
                                class="shrink-0 rounded-md bg-canvas px-2.5 py-1 font-mono text-xs font-semibold text-neutral-500">68%</span>
                        </div>

                        <div class="grid gap-3 min-[520px]:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
                            <div class="rounded-md border border-hairline bg-canvas p-3">
                                <p class="deck-label mb-3 text-neutral-400">Backlog</p>
                                <div class="rounded-md border border-l-2 border-hairline border-l-border bg-white p-3 text-sm font-medium">
                                    Invite team members</div>
                            </div>
                            <div class="rounded-md border border-hairline bg-canvas p-3">
                                <p class="deck-label mb-3 text-neutral-400">In progress</p>
                                <div class="rounded-md border border-l-2 border-hairline border-l-border bg-white p-3 text-sm font-medium">
                                    Sprint planning panel</div>
                            </div>
                            <div class="rounded-md border border-hairline bg-canvas p-3">
                                <p class="deck-label mb-3 text-neutral-400">Done</p>
                                <div class="rounded-md border border-l-2 border-hairline border-l-border bg-white p-3 text-sm font-medium">Issue
                                    comments</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="flex items-center justify-center p-5 sm:p-8 lg:p-10">
                    <div class="w-full max-w-md">
                        <a href="{{ url('/') }}" wire:navigate
                            class="mb-10 flex w-fit items-center gap-3 font-semibold tracking-tight text-ink lg:hidden">
                            <img src="{{ asset('scrumlab-icon.svg') }}" alt="" class="size-10 shrink-0">
                            <span class="font-display tracking-tight">{{ config('app.name') }}</span>
                        </a>

                        <div class="mb-8">
                            <p class="deck-label text-accent">Sign in</p>
                            <h2 class="mt-3 font-display text-2xl font-bold tracking-tight text-ink sm:text-3xl">Log in to
                                ScrumLab</h2>
                        </div>

                        @if ($errors->any())
                            <div class="mb-6 rounded-md border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                                Please check your email and password.
                            </div>
                        @endif

                        @if (session('status'))
                            <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                                {{ session('status') }}
                            </div>
                        @endif

                        <form action="{{ route('login.store') }}" method="POST" class="space-y-5">
                            @csrf

                            <div>
                                <label for="email" class="block text-sm font-semibold text-ink">Email
                                    address</label>
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

                            <div>
                                <label for="password"
                                    class="block text-sm font-semibold text-ink">Password</label>
                                <div class="relative mt-2">
                                    <input type="password" id="password" name="password"
                                        autocomplete="current-password" required
                                        @class([
                                            'block w-full rounded-md border bg-white px-3.5 py-3 pr-12 text-sm text-ink outline-none transition placeholder:text-neutral-400 focus:border-accent focus:ring-2 focus:ring-accent/20',
                                            'border-rose-300' => $errors->has('password'),
                                            'border-hairline' => ! $errors->has('password'),
                                        ])>
                                    <button type="button" data-password-toggle="password" aria-label="Show password"
                                        class="absolute inset-y-0 right-0 grid w-11 place-items-center text-neutral-500 transition hover:text-ink">
                                        <svg class="size-5" data-eye-icon xmlns="http://www.w3.org/2000/svg"
                                            fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"
                                            aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                        <svg class="hidden size-5" data-eye-off-icon xmlns="http://www.w3.org/2000/svg"
                                            fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"
                                            aria-hidden="true">
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

                            <div
                                class="flex flex-col gap-3 text-sm min-[420px]:flex-row min-[420px]:items-center min-[420px]:justify-between">
                                <label class="flex items-center gap-2 text-neutral-500" for="remember_me">
                                    <input id="remember_me" name="remember_me" type="checkbox"
                                        class="size-4 rounded border-hairline text-accent focus:ring-accent">
                                    Remember me
                                </label>

                                <a href="{{ route('password.request') }}" wire:navigate
                                    class="font-semibold text-accent transition hover:text-accent-strong">Forgot
                                    password?</a>
                            </div>

                            <button type="submit"
                                class="flex w-full justify-center rounded-md bg-accent px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-strong focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2">
                                Sign in
                            </button>
                        </form>

                        <p class="mt-8 text-center text-sm text-neutral-500">
                            New to ScrumLab?
                            <a href="{{ route('register') }}" wire:navigate
                                class="font-semibold text-accent transition hover:text-accent-strong">Create
                                an account</a>
                        </p>
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
    </script>
</x-layout>
