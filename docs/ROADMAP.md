# Roadmap — Se Aprender

Cada fase termina com algo utilizável, testado e registrado em [PROGRESSO.md](PROGRESSO.md). Tamanho relativo: P, M, G.

## Fase 0 — Base (P)

Projeto Laravel, documentação, landing com cadastro e login, painel inicial.

## Fase 1 — Fundação (M)

Livewire, PostgreSQL via Docker, design system (botões, cards, modais, toasts, estados), layout autenticado, recuperação de senha, limite de tentativas no cadastro, perfis, Policies, turmas, catálogo de turmas com inscrição, matrículas por código e CSV, auditoria, seeders.

**Pronto quando:** professor cria turma, aluno cria a conta, procura a turma e se inscreve, e ninguém vê o conteúdo de uma turma em que não está.

## Fase 2 — Atividades e entregas (M)

Atividades, cronograma, anexos, upload privado, rascunho, envio, versões, correção, devolutiva, reenvio.

Já existem o mural, os anexos e o upload privado. Falta a entrega do aluno em diante.

**Pronto quando:** atividade publicada, entregue, corrigida e devolvida.

## Fase 3 — Pixel Quiz (M)

Banco de questões, engine de jogos, Arcade Studio para o Pixel Quiz, preview, publicação, clonagem.

Já existe o quiz simples de múltipla escolha. Faltam tempo, imagem, banco de questões e clonagem.

**Pronto quando:** professor cria e testa um quiz com imagem e tempo.

## Fase 4 — Arcade Live (G)

Reverb, QR local, salas, lobby, projetor, ingresso, partida, timer, ranking, pódio, reconexão, polling, rate limits, concorrência, carga com 50 jogadores.

**Pronto quando:** professor e dois alunos em sessões distintas jogam até o pódio, e o teste de carga passa.

## Fase 5 — Economia e loja (M)

Livro-razão, regras por turma, tetos, participação rápida, crédito da partida, vínculo de convidado, loja, resgate, aprovação, estorno, histórico.

**Pronto quando:** o critério de aceite completo roda de ponta a ponta. **Aqui o MVP fecha.**

## Fase 6 — Avatares e assets (M)

Download e conferência de licenças, editor de avatar, inventário, animações, dashboard RPG do aluno.

## Fase 7 — Jogos adicionais (G)

Verdadeiro ou Falso, Ordene as Etapas, Jogo da Memória, Code Quest, Complete o Código, Batalha de Equipes, Roleta do Conhecimento, Escape Room.

## Fase 8 — Jornada (M)

Missões, mapa, medalhas, baús, equipes, biblioteca em módulos, portfólio, retrospectiva.

## Fase 9 — Relatórios, IA e admin (M)

Relatórios com CSV/PDF, painel do professor completo, administração, IA assistiva opcional.

## Fase 10 — QA e entrega (M)

Instalação limpa, auditoria de acessibilidade e performance, revisão de segurança, teste em rede real, README, Docker Compose, checklist de produção, demonstração do fluxo.

## Decisões pendentes

| Decisão | Necessária até | Sugestão |
| --- | --- | --- |
| Arquivo original da logo (PNG/SVG) | Fase 1 | Salvar em `public/images/` |
| Banco oficial | Fase 1 | PostgreSQL via Docker |
| Hospedagem e domínio | Fase 4 | VPS com TLS |
| WebSocket na rede da escola | Fase 4 | Testar em sala antes do uso |
| Política LGPD com a coordenação | Antes de dados reais | Definir base legal, retenção e menores |
| Recompensas ligadas a nota | Antes de habilitar | Mantidas bloqueadas |
| Provedor de IA | Fase 9 | Opcional |
