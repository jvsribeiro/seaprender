# Progresso

Atualizado em 09/11/2026. Ao retomar, continuar pelo primeiro item aberto.

## Fase 0 — Base

- [x] Projeto Laravel 13.40 com PHP 8.4, SQLite, Tailwind 4 e Vite 8
- [x] Nome, idioma pt_BR e fuso America/Sao_Paulo configurados
- [x] Prompt mestre revisado e validado
- [x] PRD, arquitetura, roadmap, créditos e checklist de aceite
- [x] Landing de uma tela com cadastro e login
- [x] Cadastro, login e logout com Fortify
- [x] Painel inicial protegido por autenticação
- [x] Mensagens de erro em português

### Evidências

| Verificação | Resultado |
| --- | --- |
| `php artisan test` | 12 testes, 41 asserções, todos passando |
| `npm run build` | Concluído |
| Navegador, 360px | Erro de senha, cadastro, painel, logout, senha errada e login corretos |
| Navegador, 1280×800 | Sem rolagem nem estouro horizontal |
| Teclado | Tab percorre abas e campos, com foco visível |
| `pint --test` | Passando |
| Contraste da paleta | Calculado; regras no prompt mestre |

## Fase 1 — Fundação

- [x] PostgreSQL 17 e Redis 8 via Docker
- [x] Livewire 4 e Boost
- [x] Design system: botões, cards, badges, modal, toasts, estados vazios e páginas de erro
- [x] Layout autenticado com menu lateral e menu móvel
- [x] Recuperação de senha, sem revelar quais e-mails têm conta
- [x] Limite de cadastros por rede
- [x] Perfis, Policies e troca de perfil pela administração
- [x] Turmas: criar, editar, arquivar, reativar e trocar código
- [x] Catálogo: o aluno busca a turma e se inscreve
- [x] Matrícula por código, link de convite, inclusão manual e CSV
- [x] Auditoria e seeders

### Evidências

| Verificação | Resultado |
| --- | --- |
| `php artisan test` em SQLite | 130 testes, todos passando |
| `php artisan test` em PostgreSQL | 130 testes, todos passando |
| `npm run build` | Concluído |
| Navegador, 452px | Cadastro entra logado, busca por professor, inscrição e toast, sem estouro horizontal |
| Navegador, 1280×800 | Lista de alunos, modal com foco e Esc, troca de código |
| Navegador, outra professora | Turma alheia devolve 404 |

## Ferramentas de aula

Adiantado das fases 2, 3 e 9 a pedido do professor.

- [x] Logo original em todas as telas
- [x] Mural com aviso, atividade, exercício, slide, link e quiz
- [x] Curtidas e registro de quem viu cada publicação
- [x] Arquivos privados da turma, com download autorizado
- [x] Matérias da turma, com filtro no mural
- [x] Quiz: rascunho, publicação, resposta única, correção e resultados
- [x] Espaço para IA no editor de quiz, ainda sem provedor conectado
- [x] Relatório de acessos da turma
- [x] Auditoria com login, tentativa errada, turma aberta, download e filtros
- [x] Ficha do usuário, senha temporária com troca obrigatória e exclusão

### Evidências

| Verificação | Resultado |
| --- | --- |
| Navegador, 1280×800 | Professor, aluna e administração em todas as abas |
| Navegador, 375px | Mural e resultado do quiz sem estouro horizontal |
| Slide com PDF | Envio, publicação e download reais pelo navegador |
| Quiz | Sem gabarito na página antes do envio, 2 de 3 após responder |

### Pendências

- [ ] Entrega de atividade pelo aluno
- [ ] Comentários no mural
- [ ] Atualização ao vivo do mural com Reverb
- [ ] Provedor real de IA
- [ ] Verificação de vírus nos arquivos enviados
- [ ] Limite por IP em "Esqueci a senha"
- [ ] Content-Security-Policy

Fases seguintes em [ROADMAP.md](ROADMAP.md).

## Participação, progresso e identidade da turma

- [x] Progresso por aluno e por turma com publicações abertas, quizzes e reflexões
- [x] Resumos particulares e resultados recentes de partidas no progresso do aluno
- [x] Reflexão de fim de aula e votação com um voto por aluno no mural
- [x] Quiz ao vivo com sala de espera, tempo por pergunta, gabarito e classificação
- [x] Sorteio de alunos sem repetição no ciclo e formação de grupos
- [x] Lojinha com saldo por turma, catálogo, estoque, entrega e estorno
- [x] Auditoria por turma para o professor com categorias, ícones e detalhes
- [x] Paletas e padrões de fundo com prévia e personalização por turma
- [ ] Piloto com rede da escola, modo projetor e teste de carga com 50 jogadores
