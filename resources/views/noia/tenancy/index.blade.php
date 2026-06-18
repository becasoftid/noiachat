<x-layouts.noia title="Empresa" header="Empresa, sedes y membresias">
    <div class="space-y-6">
        @if(auth()->user()->hasRole('super_admin'))
            <section class="noia-card">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-950">Empresas</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $companies->count() }} empresas registradas en la plataforma.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('tenancy.companies.store') }}" class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-7">
                    @csrf
                    <input class="noia-input" name="name" value="{{ old('name') }}" placeholder="Nombre" required>
                    <input class="noia-input" name="slug" value="{{ old('slug') }}" placeholder="slug" required>
                    <input class="noia-input" name="legal_name" value="{{ old('legal_name') }}" placeholder="Razon social">
                    <input class="noia-input" name="tax_id" value="{{ old('tax_id') }}" placeholder="Identificacion">
                    <input class="noia-input" name="timezone" value="{{ old('timezone', 'America/Bogota') }}" placeholder="Zona horaria" required>
                    <select class="noia-select" name="status" required>
                        <option value="active" @selected(old('status', 'active') === 'active')>Activa</option>
                        <option value="inactive" @selected(old('status') === 'inactive')>Inactiva</option>
                    </select>
                    <button class="noia-btn-success">Crear empresa</button>
                    <input class="noia-input md:col-span-1 xl:col-span-2" name="default_branch_name" value="{{ old('default_branch_name') }}" placeholder="Sede inicial">
                    <input class="noia-input md:col-span-1 xl:col-span-2" name="default_branch_code" value="{{ old('default_branch_code', 'principal') }}" placeholder="Codigo sede inicial">
                </form>

                <div class="mt-6 overflow-x-auto rounded-lg border border-slate-200">
                    <table class="noia-table">
                        <thead>
                            <tr>
                                <th>Empresa</th>
                                <th>Slug</th>
                                <th>Identificacion</th>
                                <th>Zona</th>
                                <th>Estado</th>
                                <th>Sedes</th>
                                <th>Membresias</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($companies as $managedCompany)
                                <tr>
                                    <td>
                                        <input form="company-{{ $managedCompany->id }}" class="noia-input min-w-52" name="name" value="{{ $managedCompany->name }}" required>
                                        <input form="company-{{ $managedCompany->id }}" type="hidden" name="legal_name" value="{{ $managedCompany->legal_name }}">
                                    </td>
                                    <td>
                                        <input form="company-{{ $managedCompany->id }}" class="noia-input min-w-40" name="slug" value="{{ $managedCompany->slug }}" required>
                                    </td>
                                    <td>
                                        <input form="company-{{ $managedCompany->id }}" class="noia-input min-w-40" name="tax_id" value="{{ $managedCompany->tax_id }}">
                                    </td>
                                    <td>
                                        <input form="company-{{ $managedCompany->id }}" class="noia-input min-w-44" name="timezone" value="{{ $managedCompany->timezone }}" required>
                                    </td>
                                    <td>
                                        <select form="company-{{ $managedCompany->id }}" class="noia-select min-w-32" name="status">
                                            <option value="active" @selected($managedCompany->status === 'active')>Activa</option>
                                            <option value="inactive" @selected($managedCompany->status === 'inactive')>Inactiva</option>
                                        </select>
                                    </td>
                                    <td>{{ $managedCompany->branches_count }}</td>
                                    <td>{{ $managedCompany->memberships_count }}</td>
                                    <td class="text-right">
                                        <form id="company-{{ $managedCompany->id }}" method="POST" action="{{ route('tenancy.companies.update', $managedCompany) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="noia-btn-secondary">Guardar</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <section class="noia-card">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-950">Empresa activa</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $company->slug }}</p>
                </div>
                <span class="noia-badge {{ $company->status === 'active' ? 'noia-badge-success' : 'noia-badge-neutral' }}">
                    {{ $company->status === 'active' ? 'Activa' : 'Inactiva' }}
                </span>
            </div>

            <form method="POST" action="{{ route('tenancy.company.update') }}" class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                @csrf
                @method('PATCH')
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Nombre</label>
                    <input class="noia-input mt-2" name="name" value="{{ old('name', $company->name) }}" required>
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Razon social</label>
                    <input class="noia-input mt-2" name="legal_name" value="{{ old('legal_name', $company->legal_name) }}">
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Identificacion</label>
                    <input class="noia-input mt-2" name="tax_id" value="{{ old('tax_id', $company->tax_id) }}">
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Zona horaria</label>
                    <input class="noia-input mt-2" name="timezone" value="{{ old('timezone', $company->timezone) }}" required>
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Estado</label>
                    <select class="noia-select mt-2" name="status">
                        <option value="active" @selected(old('status', $company->status) === 'active')>Activa</option>
                        <option value="inactive" @selected(old('status', $company->status) === 'inactive')>Inactiva</option>
                    </select>
                </div>
                <div class="md:col-span-2 xl:col-span-5">
                    <button class="noia-btn-primary">Guardar empresa</button>
                </div>
            </form>
        </section>

        <section class="noia-card">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-950">Sedes</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $branches->count() }} sedes configuradas en la empresa activa.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('tenancy.branches.store') }}" class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-6">
                @csrf
                <input class="noia-input" name="name" value="{{ old('name') }}" placeholder="Nombre de sede" required>
                <input class="noia-input" name="code" value="{{ old('code') }}" placeholder="Codigo">
                <input class="noia-input" name="city" value="{{ old('city') }}" placeholder="Ciudad">
                <input class="noia-input" name="address" value="{{ old('address') }}" placeholder="Direccion">
                <input class="noia-input" name="timezone" value="{{ old('timezone', $company->timezone) }}" placeholder="Zona horaria">
                <label class="flex h-11 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-100">
                    Activa
                </label>
                <div class="md:col-span-2 xl:col-span-6">
                    <button class="noia-btn-success">Crear sede</button>
                </div>
            </form>

            <div class="mt-6 overflow-x-auto rounded-lg border border-slate-200">
                <table class="noia-table">
                    <thead>
                        <tr>
                            <th>Sede</th>
                            <th>Codigo</th>
                            <th>Ciudad</th>
                            <th>Direccion</th>
                            <th>Zona</th>
                            <th>Activa</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($branches as $branch)
                            <tr>
                                <td>
                                    <input form="branch-{{ $branch->id }}" class="noia-input min-w-48" name="name" value="{{ $branch->name }}" required>
                                </td>
                                <td>
                                    <input form="branch-{{ $branch->id }}" class="noia-input min-w-32" name="code" value="{{ $branch->code }}">
                                </td>
                                <td>
                                    <input form="branch-{{ $branch->id }}" class="noia-input min-w-40" name="city" value="{{ $branch->city }}">
                                </td>
                                <td>
                                    <input form="branch-{{ $branch->id }}" class="noia-input min-w-56" name="address" value="{{ $branch->address }}">
                                </td>
                                <td>
                                    <input form="branch-{{ $branch->id }}" class="noia-input min-w-44" name="timezone" value="{{ $branch->timezone }}">
                                </td>
                                <td>
                                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                                        <input form="branch-{{ $branch->id }}" type="checkbox" name="is_active" value="1" @checked($branch->is_active) class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-100">
                                        Si
                                    </label>
                                </td>
                                <td class="text-right">
                                    <form id="branch-{{ $branch->id }}" method="POST" action="{{ route('tenancy.branches.update', $branch) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="noia-btn-secondary">Guardar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-slate-500">No hay sedes registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="noia-card">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-950">Membresias</h3>
                    <p class="mt-1 text-sm text-slate-500">Roles y sedes asignadas dentro de {{ $company->name }}.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('tenancy.memberships.store') }}" class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-6">
                @csrf
                <select class="noia-select" name="user_id" required>
                    <option value="">Usuario</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((int) old('user_id') === $user->id)>{{ $user->name }} · {{ $user->email }}</option>
                    @endforeach
                </select>
                <select class="noia-select" name="branch_id">
                    <option value="">Toda la empresa</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(old('branch_id') === $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
                <select class="noia-select" name="role_id" required>
                    <option value="">Rol</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" @selected((int) old('role_id') === $role->id)>{{ $role->label }}</option>
                    @endforeach
                </select>
                <label class="flex h-11 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700">
                    <input type="checkbox" name="is_default" value="1" class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-100">
                    Predeterminada
                </label>
                <label class="flex h-11 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-100">
                    Activa
                </label>
                <button class="noia-btn-success">Asignar</button>
            </form>

            <div class="mt-6 overflow-x-auto rounded-lg border border-slate-200">
                <table class="noia-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Alcance</th>
                            <th>Rol</th>
                            <th>Default</th>
                            <th>Activa</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($memberships as $membership)
                            <tr>
                                <td>
                                    <select form="membership-{{ $membership->id }}" class="noia-select min-w-64" name="user_id" required>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" @selected($membership->user_id === $user->id)>{{ $user->name }} · {{ $user->email }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select form="membership-{{ $membership->id }}" class="noia-select min-w-48" name="branch_id">
                                        <option value="" @selected($membership->branch_id === null)>Toda la empresa</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" @selected($membership->branch_id === $branch->id)>{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select form="membership-{{ $membership->id }}" class="noia-select min-w-40" name="role_id" required>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->id }}" @selected($membership->role_id === $role->id)>{{ $role->label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                                        <input form="membership-{{ $membership->id }}" type="checkbox" name="is_default" value="1" @checked($membership->is_default) class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-100">
                                        Si
                                    </label>
                                </td>
                                <td>
                                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                                        <input form="membership-{{ $membership->id }}" type="checkbox" name="is_active" value="1" @checked($membership->is_active) class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-100">
                                        Si
                                    </label>
                                </td>
                                <td class="text-right">
                                    <form id="membership-{{ $membership->id }}" method="POST" action="{{ route('tenancy.memberships.update', $membership) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="noia-btn-secondary">Guardar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-slate-500">No hay membresias registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                {{ $memberships->links() }}
            </div>
        </section>
    </div>
</x-layouts.noia>
