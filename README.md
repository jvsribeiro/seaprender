# Se Aprender

Plataforma de ensino gamificada para turmas de tecnologia. Laravel 13, Livewire 4, Tailwind 4, PostgreSQL e Vite.

## Rodar o projeto

Com o Docker aberto:

```bash
docker compose up -d postgres
```

```bash
composer run dev
```

Abra http://localhost:8000.

## Instalar em outra máquina

```bash
composer run setup
```

## Dados de demonstração

```bash
php artisan db:seed
```

Cria administração, dois professores, duas turmas, 15 alunos fictícios e um mural de exemplo com quiz. Pode rodar de novo sem duplicar nada, e não roda em produção.

| Perfil | E-mail |
| --- | --- |
| Administração | `admin@example.test` |
| Professor | `joao.ribeiro@example.test` |
| Aluna | `ana.beatriz.souza@example.test` |

A senha das três é a de `DEMO_PASSWORD` no `.env`.

## Dar aula

Tudo fica dentro da turma, em quatro abas.

- **Mural:** avisos, atividades, exercícios, slides e links. Os alunos veem e curtem, e cada publicação mostra quem ainda não viu.
- **Quizzes:** clique em **Criar quiz**, escreva as perguntas, marque a alternativa certa e publique no mural. Cada aluno responde uma vez.
- **Alunos:** adicionar, importar CSV, remover e gerar senha temporária.
- **Acessos:** último acesso e o que cada aluno viu e respondeu.

A administração tem a ficha de cada pessoa em `/admin/usuarios`, com histórico, senha temporária e exclusão, e o registro completo em `/admin/auditoria`.

## Envio de arquivos

O mural aceita arquivos de até 12 MB, mas o PHP precisa permitir esse tamanho. No `php.ini`:

```ini
upload_max_filesize = 12M
post_max_size = 16M
```

## Conectar uma IA

A tela de quiz já tem o espaço para gerar perguntas. Para ligar, crie uma classe que implemente `AppContractsQuizQuestionGenerator` e troque o vínculo em `AppServiceProvider`. As perguntas chegam no editor para o professor revisar, nada é publicado sozinho.

## Perfis

Toda conta nova é de aluno. Para tornar uma conta professor ou administrador:

```bash
php artisan app:set-user-role email@exemplo.com teacher
```

Depois disso, a administração também troca perfis em `/admin/usuarios`.

## Testes

```bash
php artisan test
```

## Documentação

- [Prompt mestre](docs/PROMPT_MESTRE.md)
- [Validação do prompt](docs/VALIDACAO_PROMPT.md)
- [PRD](docs/PRD.md)
- [Arquitetura](docs/ARQUITETURA.md)
- [Roadmap](docs/ROADMAP.md)
- [Progresso](docs/PROGRESSO.md)
- [Checklist de aceite](docs/CHECKLIST_ACEITE.md)
- [Créditos de assets](docs/ASSET_CREDITS.md)
