<x-app-layout title="Masuk - Alam Buah Segar">
    <div class="flex min-h-screen w-full items-center justify-center p-4 lg:p-8 bg-white">

        <!-- Main Card Container -->
        <div class="w-full max-w-md">

            <!-- Clean Floating Card -->
            <div class="bg-white p-8 sm:p-10 rounded-3xl border border-slate-200/80 shadow-xl shadow-slate-100">

                <!-- Minimal Header / Brand Typography -->
                <div class="text-center mb-8">
                    <h1 class="text-2xl font-black tracking-tight">
                        <span class="text-abs-green-600">ALAM</span> <span class="text-abs-orange-600">BUAH SEGAR</span>
                    </h1>
                    <p class="mt-1 text-[11px] font-bold tracking-widest text-slate-400 uppercase">
                        POS & Inventory System
                    </p>
                </div>

                <!-- Form Login -->
                <form action="{{ route('login.store') }}" method="POST" novalidate class="space-y-5">
                    @csrf

                    <!-- Username Field -->
                    <div>
                        <label for="username"
                            class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                            Username
                        </label>
                        <div class="relative">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <i data-lucide="user" class="w-4 h-4"></i>
                            </div>
                            <input id="username" name="username" type="text" value="{{ old('username') }}"
                                class="block w-full rounded-xl border-0 bg-slate-50 py-3 pl-10 pr-4 text-slate-900 ring-1 ring-inset {{ $errors->has('username') ? 'ring-red-500 focus:ring-red-500 bg-red-50/20' : 'ring-slate-200 focus:ring-abs-green-600 focus:bg-white' }} placeholder:text-slate-400 focus:ring-2 focus:ring-inset text-sm transition"
                                placeholder="Masukkan username" autofocus>
                        </div>
                        @error('username')
                            <p class="mt-2 text-xs text-red-600 flex items-center gap-1 font-medium">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>

                    <!-- Password Field -->
                    <div x-data="{ showPassword: false }">
                        <label for="password"
                            class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                            Password
                        </label>
                        <div class="relative">
                            <div
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <i data-lucide="lock" class="w-4 h-4"></i>
                            </div>
                            <input id="password" name="password" :type="showPassword ? 'text' : 'password'"
                                class="block w-full rounded-xl border-0 bg-slate-50 py-3 pl-10 pr-10 text-slate-900 ring-1 ring-inset {{ $errors->has('password') ? 'ring-red-500 focus:ring-red-500 bg-red-50/20' : 'ring-slate-200 focus:ring-abs-green-600 focus:bg-white' }} placeholder:text-slate-400 focus:ring-2 focus:ring-inset text-sm transition"
                                placeholder="••••••••">
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600 transition">
                                <i data-lucide="eye" x-show="!showPassword" class="w-4 h-4"></i>
                                <i data-lucide="eye-off" x-show="showPassword" class="w-4 h-4" x-cloak></i>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-2 text-xs text-red-600 flex items-center gap-1 font-medium">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>

                    <!-- Remember Me Option -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input id="remember" name="remember" type="checkbox"
                                class="h-4 w-4 rounded border-slate-300 text-abs-green-600 focus:ring-abs-green-600">
                            <span class="text-xs text-slate-600 hover:text-slate-800 transition font-medium">Ingat
                                saya</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-abs-green-600 px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-abs-green-600/20 hover:bg-abs-green-700 focus:outline-none focus:ring-2 focus:ring-abs-green-600 focus:ring-offset-2 transition active:scale-[0.99]">
                            <span>Masuk</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </div>

                </form>

            </div>

            <!-- Minimal Footer Copyright -->
            <p class="mt-6 text-center text-xs text-slate-400 font-medium">
                © {{ date('Y') }} Alam Buah Segar
            </p>

        </div>

    </div>
</x-app-layout>
