<div>
    <x-ui.page-header title="Usuários" />

    <div class="mb-5 flex flex-wrap items-end gap-4">
        <div class="w-full max-w-sm">
            <label for="busca" class="mb-1 block text-sm font-semibold">Buscar por nome ou e-mail</label>
            <input id="busca" type="search" class="field-input" wire:model.live.debounce.300ms="search">
        </div>
        <label class="flex items-center gap-2 pb-3 text-sm font-medium">
            <input type="checkbox" class="size-4 accent-azul" wire:model.live="neverAccessed">
            Só quem nunca acessou
        </label>
    </div>

    @error('role')
        <p class="alert mb-5" role="alert">{{ $message }}</p>
    @enderror

    <div class="card">
        @if ($users->isEmpty())
            <x-ui.empty title="Nada encontrado" />
        @else
            <ul class="divide-y-2 divide-noite/10">
                @foreach ($users as $user)
                    <li class="flex flex-wrap items-center justify-between gap-3 py-2.5" wire:key="user-{{ $user->id }}">
                        <div class="flex min-w-0 flex-1 basis-48 items-center gap-3">
                            <x-ui.avatar :name="$user->name" />
                            <div class="min-w-0">
                                <a class="block truncate font-semibold text-azul underline underline-offset-2" href="{{ route('admin.users.show', $user) }}" wire:navigate>{{ $user->name }}</a>
                                <p class="truncate text-sm text-noite/75">{{ $user->email }}</p>
                                <p class="text-sm text-noite/75">{{ $user->last_seen_at ? 'Acessou '.$user->last_seen_at->diffForHumans() : 'Nunca acessou' }}</p>
                            </div>
                        </div>
                        <div class="w-44">
                            <label for="perfil-{{ $user->id }}" class="sr-only">Perfil de {{ $user->name }}</label>
                            <select
                                id="perfil-{{ $user->id }}"
                                class="field-input py-1.5 text-sm"
                                wire:change="changeRole({{ $user->id }}, $event.target.value)"
                                @disabled($user->is(auth()->user()))
                            >
                                @foreach ($roles as $role)
                                    <option value="{{ $role->value }}" @selected($user->role === $role) wire:key="role-{{ $user->id }}-{{ $role->value }}">{{ $role->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="mt-3">{{ $users->links('components.ui.pagination') }}</div>
        @endif
    </div>
</div>
