<div class="grid gap-5">
    @error('shop')
        <p class="alert" role="alert">{{ $message }}</p>
    @enderror

    @if ($canManage && $classroom->isActive())
        <div class="grid gap-4 lg:grid-cols-2">
            <x-ui.card title="Conceder pontos">
                <p class="mt-1 text-sm">Reconheça uma participação ou atividade com pontos e um motivo.</p>
                @if ($students->isEmpty())
                    <p class="mt-3 text-sm font-semibold">Matricule um aluno para conceder pontos.</p>
                @else
                    <form wire:submit="award" class="mt-3 grid gap-3">
                        <div>
                            <label for="reward-student" class="mb-1 block text-sm font-semibold">Aluno</label>
                            <select id="reward-student" class="field-input" wire:model="studentId" required>
                                <option value="">Selecione um aluno</option>
                                @foreach ($students as $student)
                                    <option value="{{ $student->id }}" wire:key="reward-student-{{ $student->id }}">{{ $student->name }}</option>
                                @endforeach
                            </select>
                            @error('studentId') <p class="text-sm text-erro" role="alert">{{ $message }}</p> @enderror
                        </div>
                        <x-ui.input name="points" label="Pontos" type="number" min="1" max="1000" required />
                        <x-ui.input name="reason" label="Motivo" maxlength="160" required />
                        <x-ui.button type="submit" class="justify-self-start">Conceder pontos</x-ui.button>
                    </form>
                @endif
            </x-ui.card>

            <x-ui.card title="Liberar brinde">
                <p class="mt-1 text-sm">Defina o preço em pontos e a quantidade disponível.</p>
                <form wire:submit="createReward" class="mt-3 grid gap-3">
                    <x-ui.input name="rewardTitle" label="Nome do brinde" maxlength="100" required />
                    <x-ui.input name="description" label="Descrição opcional" maxlength="500" />
                    <div class="grid grid-cols-2 gap-3">
                        <x-ui.input name="cost" label="Custo em pontos" type="number" min="1" max="10000" required />
                        <x-ui.input name="stock" label="Estoque" type="number" min="1" max="1000" required />
                    </div>
                    <x-ui.button type="submit" class="justify-self-start">Liberar brinde</x-ui.button>
                </form>
            </x-ui.card>
        </div>
    @elseif (! $canManage)
        <x-ui.card>
            <div class="flex items-center gap-4">
                <x-pixel name="coin" class="w-12 shrink-0" />
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-noite/75">Seu saldo nesta turma</p>
                    <h2 class="font-marca text-3xl font-bold text-azul">{{ $balance }} pontos</h2>
                    <p class="text-sm">Troque pontos por brindes que o professor liberar.</p>
                </div>
            </div>
        </x-ui.card>
    @endif

    <x-ui.card title="Catálogo da turma">
        @if ($catalog->isEmpty())
            <x-ui.empty title="A lojinha ainda está vazia" sprite="chest">Os brindes aparecem aqui assim que forem liberados.</x-ui.empty>
        @else
            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($catalog as $reward)
                    <article class="grid min-w-0 content-start gap-2 border-2 border-noite/10 bg-creme/50 p-3" wire:key="reward-{{ $reward->id }}">
                        <div class="flex items-start justify-between gap-2">
                            <x-pixel name="chest" class="w-9 shrink-0" />
                            <x-ui.badge :tone="$reward->enabled && $reward->stock > 0 ? 'blue' : 'muted'">
                                {{ ! $reward->enabled ? 'Pausado' : ($reward->stock > 0 ? $reward->stock.' em estoque' : 'Esgotado') }}
                            </x-ui.badge>
                        </div>
                        <h3 class="break-words font-marca text-lg font-semibold text-azul">{{ $reward->title }}</h3>
                        @if ($reward->description)
                            <p class="break-words text-sm">{{ $reward->description }}</p>
                        @endif
                        <p class="font-bold text-azul">{{ $reward->cost }} pontos</p>
                        @if ($classroom->isActive())
                            @if ($canManage)
                                <x-ui.button variant="secondary" class="justify-self-start" wire:click="setRewardEnabled({{ $reward->id }}, {{ $reward->enabled ? 'false' : 'true' }})" wire:loading.attr="disabled">
                                    {{ $reward->enabled ? 'Pausar brinde' : 'Reativar brinde' }}
                                </x-ui.button>
                            @elseif ($canRedeem && $reward->stock > 0 && $balance >= $reward->cost)
                                <div>
                                    <x-ui.confirm title="Resgatar este brinde?" label="Confirmar resgate" action="redeem({{ $reward->id }})">
                                        <x-slot:trigger><x-ui.button>Resgatar</x-ui.button></x-slot:trigger>
                                        Serão usados {{ $reward->cost }} pontos do seu saldo. Acompanhe a entrega em Meus resgates.
                                    </x-ui.confirm>
                                </div>
                            @elseif ($canRedeem && $reward->stock > 0)
                                <p class="text-sm">Faltam {{ $reward->cost - $balance }} pontos.</p>
                            @endif
                        @endif
                    </article>
                @endforeach
            </div>
            <div class="mt-4">{{ $catalog->links('components.ui.pagination') }}</div>
        @endif
    </x-ui.card>

    <x-ui.card :title="$canManage ? 'Solicitações de resgate' : 'Meus resgates'">
        <ul class="mt-2 divide-y divide-noite/10">
            @forelse ($redemptions as $redemption)
                <li class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="redemption-{{ $redemption->id }}">
                    <div class="min-w-0">
                        <p class="break-words font-semibold text-azul">{{ $redemption->title }}</p>
                        <p class="text-sm">{{ $redemption->cost }} pontos @if ($canManage) · {{ $redemption->name ?? 'Conta excluída' }} @endif</p>
                    </div>
                    <x-ui.badge :tone="$redemption->status === 'pending' ? 'blue' : 'muted'">
                        {{ match ($redemption->status) { 'delivered' => 'Entregue', 'cancelled' => 'Cancelado', default => 'Aguardando entrega' } }}
                    </x-ui.badge>
                    @if ($canManage && $redemption->status === 'pending')
                        <div class="flex flex-wrap gap-2">
                            <x-ui.button wire:click="settle({{ $redemption->id }}, true)" wire:loading.attr="disabled">Marcar entregue</x-ui.button>
                            <x-ui.confirm title="Cancelar este resgate?" label="Cancelar resgate" action="settle({{ $redemption->id }}, false)">
                                <x-slot:trigger><x-ui.button variant="secondary">Cancelar</x-ui.button></x-slot:trigger>
                                O brinde volta ao estoque e os pontos voltam ao saldo do aluno.
                            </x-ui.confirm>
                        </div>
                    @endif
                </li>
            @empty
                <li class="py-3 text-sm">Nenhum resgate registrado.</li>
            @endforelse
        </ul>
        {{ $redemptions->links('components.ui.pagination') }}
    </x-ui.card>

    @unless ($canManage)
        <x-ui.card title="Movimentações recentes">
            <ul class="mt-2 divide-y divide-noite/10">
                @forelse ($entries as $entry)
                    <li class="flex items-center justify-between gap-3 py-2 text-sm" wire:key="point-entry-{{ $entry->id }}">
                        <span class="min-w-0 break-words">{{ $entry->reason }}</span>
                        <span class="shrink-0 font-bold text-azul">{{ $entry->amount > 0 ? '+' : '' }}{{ $entry->amount }} pts</span>
                    </li>
                @empty
                    <li class="py-3 text-sm">Nenhuma movimentação registrada.</li>
                @endforelse
            </ul>
        </x-ui.card>
    @endunless
</div>
