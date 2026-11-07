# Arquitetura — Se Aprender

## Visão geral

```text
Navegador (Blade + Livewire + Alpine)      Projetor / Celular
        │ HTTP                                   │ WebSocket (fallback: polling)
        ▼                                        ▼
   Laravel 13 ──── broadcast ────────────►  Reverb
        │
        ├── Actions / Services  (regras de domínio)
        ├── Policies / Form Requests
        ├── Events → Listeners → Jobs (Redis em produção)
        ▼
   PostgreSQL          Storage privado (entregas e anexos)
```

Monólito com a estrutura padrão do Laravel: `app/Actions` guarda as regras de negócio, `app/Livewire` as telas (componentes em classe), `app/Policies` as permissões e `app/Models` os dados. As áreas abaixo organizam o produto e viram subpastas dentro dessas pastas, como `app/Actions/Classrooms`.

| Área | Responsabilidade |
| --- | --- |
| `Identity` | Usuários, perfis, auditoria |
| `Classroom` | Turmas, matrículas, avisos |
| `Learning` | Conteúdos, atividades, entregas, avaliação |
| `Arcade` | Jogos, questões, engine por tipo |
| `Live` | Salas, jogadores, respostas, máquina de estados |
| `Economy` | XP, moedas, participação, loja |
| `Progress` | Missões, medalhas, mapa, avatares |
| `Reports` | Relatórios e exportações |

## Modelo de dados principal

```text
users ─┬─ enrollments ─── classrooms ─── curricular_units
       │      └─ point_transactions (livro-razão)
       ├─ avatars, inventory_items
       └─ audit_logs

classrooms ─┬─ activities ─── submissions ─── submission_versions, reviews
            ├─ participations
            ├─ rewards ─── redemptions
            └─ game_sessions

questions ─── question_options
games ─── game_items
game_sessions ─┬─ game_players
               └─ session_questions ─── game_answers
```

## Decisões técnicas

### D1. Livro-razão de pontos

`point_transactions` é somente inserção: `enrollment_id`, `currency` (`xp` ou `coin`), `amount`, `source_type`, `source_id`, `idempotency_key` (único), `created_by`. Estorno é nova linha com valor negativo. O saldo em `enrollments` é um cache atualizado na mesma transação, com `lockForUpdate`.

Chaves: `live:{session}:{enrollment}`, `submission:{id}:reward`, `participation:{id}`.

### D2. Saldo por matrícula

Moedas e XP pertencem à matrícula. Avatar e inventário pertencem ao usuário.

### D3. PIN único só entre salas ativas

Coluna `active_pin` anulável e única, zerada ao encerrar. Funciona igual em PostgreSQL e MariaDB, sem índice parcial.

### D4. Máquina de estados da sala

Transições num único `Action` com bloqueio da linha da sessão:

```text
lobby → iniciando → jogando ⇄ resposta_fechada → resultados → finalizado
qualquer estado ativo → cancelado | expirado
```

### D5. Tempo

`session_questions.closes_at` é a fonte de verdade. O evento leva `closes_at` e `server_now`; o cliente calcula o desvio e só exibe. Um job atrasado fecha a pergunta. Se ele falhar, a primeira leitura depois de `closes_at` fecha também, de forma idempotente. Respostas valem até `closes_at + 300 ms`.

### D6. Gabarito

O payload público passa por `publicPayload()`, sem `is_correct`. A correção sai somente no evento de fechamento. Um teste garante que nenhum payload de pergunta aberta contém o gabarito.

### D7. Resposta única

Índice único `(session_question_id, game_player_id)`. Violação vira resposta idempotente, sem erro para o jogador.

### D8. Engine de jogos

```php
interface GameType
{
    public function validateConfig(array $config): void;
    public function publicPayload(GameItem $item): array;
    public function evaluate(GameItem $item, array $answer): AnswerResult;
    public function score(AnswerResult $result, ScoreContext $context): int;
}
```

Tipos registrados num `GameTypeRegistry`. Cada um tem DTO de configuração e renderizador Livewire.

### D9. Identidade do jogador

Token aleatório no cookie da sala, salvo como hash em `game_players`. Reconexão usa esse token. Índices únicos: `(game_session_id, nickname)` e `(game_session_id, user_id)`.

### D10. Canais

| Canal | Quem assina | Conteúdo |
| --- | --- | --- |
| `live.{public_id}` | Jogadores, autorizados pelo token | Estado, pergunta, fechamento, ranking |
| `private-live.{id}.host` | Dono da sala | Participantes, contagem de respostas |

Fallback: `GET /live/{public_id}/state` a cada 2–3 s, com variação aleatória para não sincronizar a turma.

### D11. Rate limit

| Rota | Por jogador/sessão | Por IP |
| --- | --- | --- |
| Tentativa de PIN | 10/min | 300/min |
| Entrar na sala | 10/min | 200/min |
| Responder | 60/min | 3000/min |
| Polling | 40/min | 3000/min |

Valores iniciais, a ajustar no teste de carga.

### D12. Uploads

Disco privado, nome aleatório, download por rota com Policy. Lista de extensões e MIME por tipo de atividade. ZIP nunca extraído. Interface `MalwareScanner` com `NullScanner` e `ClamAvScanner`.

### D13. Banco

PostgreSQL oficial via Docker Compose. SQLite apenas até a Fase 1. Testes de concorrência usam banco real, em suíte separada.

### D14. Hospedagem

Reverb exige processo persistente. Alvo: VPS com Nginx, PHP-FPM, Supervisor (Reverb e filas), Redis, PostgreSQL e TLS. Hospedagem compartilhada não serve.

### D15. Autenticação

Laravel Fortify, com telas próprias. Só `registration` está ativo; login e logout vêm do pacote. Recuperação de senha entra na Fase 1 junto com o envio de e-mail. As migrations de 2FA e passkeys foram removidas por não estarem em uso; para ativar esses recursos, é preciso publicá-las de novo.

### D16. Frontend

Tokens em `resources/css/app.css`. Sprites de interface em `App\Support\PixelArt`, renderizados como SVG no servidor. Fontes locais por `@fontsource`, sem requisição a terceiros.

## Segurança

- Toda consulta de aluno passa pelo escopo da turma; Policies testadas contra IDOR.
- Rotas públicas de jogo só expõem apelido e pontuação da partida.
- `audit_logs` guarda alterações de saldo, resgates, permissões e correções.
- Conteúdo do professor renderizado com escape; HTML rico passa por sanitização.
