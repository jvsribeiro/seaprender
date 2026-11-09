<div>
    <x-ui.page-header
        :title="$classroomId ? 'Editar turma' : 'Nova turma'"
        :breadcrumbs="['Turmas' => route('classrooms.index')]"
    />

    <form wire:submit="save" class="grid w-full min-w-0 gap-5">
        <div class="card grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2"><x-ui.input name="name" label="Nome da turma" maxlength="120" /></div>
            <x-ui.input name="course" label="Curso" maxlength="120" />
            <x-ui.input name="curricular_unit" label="Unidade curricular" maxlength="120" />

            <div>
                <label for="period" class="mb-1 block text-sm font-semibold">Período</label>
                <select id="period" class="field-input" wire:model="period">
                    @foreach ($periods as $option)
                        <option value="{{ $option }}" wire:key="period-{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
                @error('period') <p class="mt-1 text-sm font-medium text-erro">{{ $message }}</p> @enderror
            </div>

            <div class="hidden sm:block"></div>
            <x-ui.input name="starts_on" label="Início" type="date" />
            <x-ui.input name="ends_on" label="Término" type="date" />
        </div>

        <x-ui.card>
            <div class="flex items-start gap-3">
                <x-pixel name="star" class="w-9 shrink-0" />
                <div>
                    <h2 class="font-marca text-xl font-semibold text-azul">Tema da turma</h2>
                    <p class="mt-1 text-sm">Escolha a aparência do fundo, do cabeçalho e dos cartões desta turma.</p>
                </div>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="theme_palette" class="mb-1 block text-sm font-semibold">Paleta</label>
                    <select id="theme_palette" class="field-input" wire:model.live="theme_palette" @error('theme_palette') aria-invalid="true" @enderror>
                        @foreach ($palettes as $key => $palette)
                            <option value="{{ $key }}" wire:key="palette-{{ $key }}">{{ $palette['label'] }}</option>
                        @endforeach
                        <option value="custom">Montar meu tema</option>
                    </select>
                    @error('theme_palette') <p class="mt-1 text-sm font-medium text-erro">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="theme_pattern" class="mb-1 block text-sm font-semibold">Padrão de fundo</label>
                    <select id="theme_pattern" class="field-input" wire:model.live="theme_pattern" @error('theme_pattern') aria-invalid="true" @enderror>
                        @foreach ($patterns as $key => $pattern)
                            <option value="{{ $key }}" wire:key="pattern-{{ $key }}">{{ $pattern['label'] }}</option>
                        @endforeach
                    </select>
                    @error('theme_pattern') <p class="mt-1 text-sm font-medium text-erro">{{ $message }}</p> @enderror
                </div>
            </div>

            @if ($theme_palette === 'custom')
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach (['theme_base' => ['Cor principal', $preview['base']], 'theme_accent' => ['Cor de destaque', $preview['accent']]] as $field => [$label, $value])
                        <div wire:key="custom-color-{{ $field }}">
                            <label for="{{ $field }}" class="mb-1 block text-sm font-semibold">{{ $label }}</label>
                            <div class="flex items-center gap-3">
                                <input id="{{ $field }}" type="color" class="size-11 cursor-pointer border-2 border-noite bg-white p-1" wire:model.live="{{ $field }}" value="{{ $value }}" @error($field) aria-invalid="true" @enderror>
                                <code class="text-sm font-semibold">{{ $value }}</code>
                            </div>
                            @error($field) <p class="mt-1 text-sm font-medium text-erro">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
            @endif

            <p class="mb-2 mt-5 text-xs font-bold uppercase tracking-wider text-noite/75">Prévia do cabeçalho</p>
            <x-classroom-theme :theme="$preview">
                <section class="hero classroom-theme-hero">
                    <x-pixel :name="$preview['sprite']" :palette="['w' => $preview['accent']]" class="absolute right-7 top-1/2 hidden w-16 -translate-y-1/2 sm:block" />
                    <div class="relative max-w-xl sm:pr-20">
                        <p class="text-sm font-bold uppercase tracking-wider text-white/90">{{ $preview['label'] }}</p>
                        <p class="mt-1 break-words font-marca text-2xl font-semibold">{{ $name ?: 'Nome da turma' }}</p>
                        <p class="mt-2 text-sm text-white/90">{{ $patterns[$preview['pattern']]['label'] }}</p>
                    </div>
                </section>
            </x-classroom-theme>
        </x-ui.card>

        <div><x-ui.button type="submit">Salvar turma</x-ui.button></div>
    </form>
</div>
