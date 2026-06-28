<x-dashboard.layout title="Profile" eyebrow="Account" :current-project="$currentProject" :projects="$projects">
    <div class="mx-auto max-w-3xl space-y-6">
        <div class="rounded-lg border border-hairline bg-white p-5">
            <h2 class="font-display text-2xl font-bold tracking-tight text-ink">Your profile</h2>
        </div>

        @if (session('status'))
            <p class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('status') }}
            </p>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" class="rounded-lg border border-hairline bg-white p-5">
            @csrf
            @method('PATCH')

            <h3 class="deck-label text-neutral-400">Profile details</h3>

            <div class="mt-4 grid gap-5">
                <div>
                    <label for="name" class="block text-sm font-semibold text-ink">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
                        class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-2.5 text-sm text-ink outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                    @error('name')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold text-ink">Email</label>
                    <input id="email" type="email" value="{{ $user->email }}" readonly
                        class="mt-2 w-full rounded-md border border-hairline bg-canvas px-3 py-2.5 text-sm text-neutral-600">
                    <p class="mt-2 text-xs text-neutral-500">Email is tied to login and project membership invites.</p>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                    class="inline-flex justify-center rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-strong">
                    Save profile
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('profile.password.update') }}" class="rounded-lg border border-hairline bg-white p-5">
            @csrf
            @method('PUT')

            <h3 class="deck-label text-neutral-400">Change password</h3>
            <p class="mt-1 text-xs text-neutral-500">Use at least 8 characters.</p>

            <div class="mt-4 grid gap-5">
                <div>
                    <label for="current_password" class="block text-sm font-semibold text-ink">Current password</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" required
                        class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-2.5 text-sm text-ink outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                    @error('current_password')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-ink">New password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required
                        class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-2.5 text-sm text-ink outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                    @error('password')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-ink">Confirm new password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                        class="mt-2 w-full rounded-md border border-hairline bg-white px-3 py-2.5 text-sm text-ink outline-none transition focus:border-accent focus:ring-2 focus:ring-accent/20">
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                    class="inline-flex justify-center rounded-md bg-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-accent-strong">
                    Update password
                </button>
            </div>
        </form>
    </div>
</x-dashboard.layout>
