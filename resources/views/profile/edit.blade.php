<x-dashboard.layout title="Profile" eyebrow="Account" :current-project="$currentProject" :projects="$projects">
    <div class="mx-auto max-w-3xl space-y-6">
        <div class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
            <h2 class="text-2xl font-bold tracking-normal text-neutral-950">Your profile</h2>
            <p class="mt-2 text-sm leading-6 text-neutral-600">
                Update how your name appears across projects, comments, and activity.
            </p>
        </div>

        @if (session('status'))
            <p class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('status') }}
            </p>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
            @csrf
            @method('PATCH')

            <h3 class="text-sm font-bold text-neutral-950">Profile details</h3>

            <div class="mt-4 grid gap-5">
                <div>
                    <label for="name" class="block text-sm font-semibold text-neutral-950">Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
                        class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
                    @error('name')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold text-neutral-950">Email</label>
                    <input id="email" type="email" value="{{ $user->email }}" readonly
                        class="mt-2 w-full rounded-md border border-neutral-200 bg-neutral-100 px-3 py-3 text-sm text-neutral-600">
                    <p class="mt-2 text-xs text-neutral-500">Email is tied to login and project membership invites.</p>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                    class="inline-flex justify-center rounded-md bg-neutral-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800">
                    Save profile
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('profile.password.update') }}" class="rounded-lg border border-neutral-200 bg-white p-5 shadow-sm">
            @csrf
            @method('PUT')

            <h3 class="text-sm font-bold text-neutral-950">Change password</h3>
            <p class="mt-1 text-xs text-neutral-500">Use at least 8 characters.</p>

            <div class="mt-4 grid gap-5">
                <div>
                    <label for="current_password" class="block text-sm font-semibold text-neutral-950">Current password</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" required
                        class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
                    @error('current_password')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-neutral-950">New password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required
                        class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
                    @error('password')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-neutral-950">Confirm new password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                        class="mt-2 w-full rounded-md border border-neutral-200 bg-stone-50 px-3 py-3 text-sm outline-none transition focus:border-neutral-950 focus:bg-white focus:ring-2 focus:ring-neutral-950/10">
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit"
                    class="inline-flex justify-center rounded-md bg-neutral-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-neutral-800">
                    Update password
                </button>
            </div>
        </form>
    </div>
</x-dashboard.layout>
