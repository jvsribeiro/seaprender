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

Abra uma turma para acessar as ferramentas abaixo.

- **Mural** reúne materiais, quizzes, votações e reflexões de fim de aula. O professor lê as reflexões. Cada aluno também pode consultar as próprias respostas.
- **Progresso** mostra publicações abertas, quizzes respondidos e reflexões enviadas. O professor acompanha os indicadores por aluno. O aluno consulta seus resumos e as últimas partidas.
- **Ferramentas** abre quizzes ao vivo com o tempo definido por pergunta, publica uma votação ou reflexão, sorteia alunos e forma grupos. Para jogar, o aluno matriculado abre a sala pelo mural.
- **Se Pergunta** guarda jogos separados dos quizzes. Monte perguntas, escolha o tema e o tempo da rodada. Use **Publicar e abrir** para colocar o convite no mural. Cada pergunta começa após a contagem de 3 segundos. Na sala, **Tela cheia** prepara a apresentação para o projetor e **Ligar som** ativa a trilha.
- **Quizzes** permite criar e publicar perguntas de múltipla escolha. Um quiz com perguntas também pode servir de base para uma partida ao vivo.
- **Lojinha** permite conceder pontos, disponibilizar brindes, controlar estoque e confirmar entregas. Cada aluno tem saldo por turma e pode acompanhar os próprios resgates. Ao cancelar um pedido pendente, o professor devolve o brinde ao estoque e os pontos ao aluno.
- **Alunos** e **Acessos** cobrem matrículas, senhas temporárias e o relatório de uso.
- **Auditoria** permite ao professor filtrar o histórico da própria turma por categoria. O registro de uma reflexão guarda o evento de envio. O texto fica no mural e no progresso do aluno.

Em **Nova turma** ou **Editar**, a seção **Tema da turma** combina paleta e padrão de fundo. **Montar meu tema** libera as duas cores com prévia do cabeçalho. O tema aparece na turma, nos cartões e nas telas de quiz.

Solicitações pelo catálogo, código ou convite ficam pendentes até o professor decidir em **Turma → Alunos**. Matrículas feitas pelo professor ou importadas por CSV já entram aprovadas. Em produção, o catálogo mostra somente as turmas em que o aluno já tem matrícula ou solicitação. Com `CLASSROOM_OPEN_ENROLLMENT=true`, outras turmas ativas aparecem no catálogo e o aluno pode solicitar inscrição sem código.

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
