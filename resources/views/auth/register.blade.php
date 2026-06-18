<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="mb-6 rounded-lg border border-cyan-100 bg-cyan-50 px-4 py-3 text-sm text-cyan-950">
            Crea tu cuenta, registra tu empresa y empieza con el plan basico de prueba.
        </div>

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Nombre del responsable')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Correo de acceso')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Company -->
        <div class="mt-6 border-t border-slate-200 pt-6">
            <p class="text-sm font-semibold text-slate-900">Empresa</p>
            <p class="mt-1 text-sm text-slate-600">Esta sera la organizacion principal del periodo de prueba.</p>
        </div>

        <div class="mt-4">
            <x-input-label for="company_name" :value="__('Nombre comercial')" />
            <x-text-input id="company_name" class="block mt-1 w-full" type="text" name="company_name" :value="old('company_name')" required autocomplete="organization" />
            <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="company_legal_name" :value="__('Razon social')" />
            <x-text-input id="company_legal_name" class="block mt-1 w-full" type="text" name="company_legal_name" :value="old('company_legal_name')" autocomplete="organization" />
            <x-input-error :messages="$errors->get('company_legal_name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="company_tax_id" :value="__('NIT / identificacion tributaria')" />
            <x-text-input id="company_tax_id" class="block mt-1 w-full" type="text" name="company_tax_id" :value="old('company_tax_id')" />
            <x-input-error :messages="$errors->get('company_tax_id')" class="mt-2" />
        </div>

        <!-- Branch -->
        <div class="mt-6 border-t border-slate-200 pt-6">
            <p class="text-sm font-semibold text-slate-900">Sede inicial</p>
        </div>

        <div class="mt-4">
            <x-input-label for="branch_name" :value="__('Nombre de la sede')" />
            <x-text-input id="branch_name" class="block mt-1 w-full" type="text" name="branch_name" :value="old('branch_name', 'Principal')" required />
            <x-input-error :messages="$errors->get('branch_name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="branch_city" :value="__('Ciudad')" />
            <x-text-input id="branch_city" class="block mt-1 w-full" type="text" name="branch_city" :value="old('branch_city')" autocomplete="address-level2" />
            <x-input-error :messages="$errors->get('branch_city')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-6 border-t border-slate-200 pt-6">
            <x-input-label for="password" :value="__('Contrasena')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirmar contrasena')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="noia-link text-sm" href="{{ route('login') }}">
                {{ __('Ya tienes cuenta?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Empezar prueba') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
