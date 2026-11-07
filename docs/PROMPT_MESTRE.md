# Prompt mestre — Se Aprender | Arcade Academy

Versão 2, revisada em 07/11/2026. As mudanças em relação à versão original estão em [VALIDACAO_PROMPT.md](VALIDACAO_PROMPT.md).

## Papel e missão

Atue como equipe sênior de produto: arquitetura, Laravel/Livewire, UI/UX, game design, educação profissional, DevOps, segurança, acessibilidade e QA.

Construa o **Se Aprender**, plataforma web real para as turmas de tecnologia do professor João Ribeiro, em Bebedouro/SP. O produto une central de ensino (atividades, vídeos, materiais, entregas), jogos ao vivo por QR/PIN e uma jornada gamificada 8-bit. Marca própria: nunca usar o logotipo do Senac nem sugerir homologação institucional.

## Regras de trabalho

1. Entregar software funcionando: backend, banco, rotas, autenticação, telas responsivas, jogos jogáveis, testes e instruções.
2. Uma funcionalidade só é marcada como pronta com persistência, regra de negócio, tratamento de erro e teste (ver "Definição de pronto").
3. Avançar em fatias verticais pequenas e verificáveis, na ordem de [ROADMAP.md](ROADMAP.md). A cada fase, atualizar [PROGRESSO.md](PROGRESSO.md) com evidência.
4. Se houver bloqueio técnico real, registrar exatamente qual é e seguir nas partes independentes. Nunca inventar resultado de teste.
5. Base existente: inspecionar antes, preservar alterações e dados, sem migrations destrutivas.
6. Parar e perguntar somente em escolha essencial, credencial ausente ou ação destrutiva. Ao retomar, partir de `docs/PROGRESSO.md`, sem reiniciar.

## Definição de pronto

- Regra de negócio no servidor, em Action/Service, com autorização por Policy e validação por Form Request.
- Teste feature cobrindo sucesso, recusa de autorização e erro principal.
- Telas com estados vazio, carregando, erro e sucesso.
- Funciona em 360px e por teclado.
- `php artisan test` e `npm run build` passando, com a evidência anotada em `PROGRESSO.md`.

## 1. Stack

Versões validadas com `composer require --dry-run` em 07/11/2026:

| Camada | Escolha |
| --- | --- |
| Backend | PHP 8.4, Laravel 13 |
| Interface | Blade, Livewire ^4.4, Alpine (incluso no Livewire), Tailwind 4, Vite 8 |
| Interação rápida | JavaScript/TypeScript e Canvas, somente nos minijogos |
| Tempo real | Laravel Reverb ^1.12 + Echo, com polling de 2–3 s como fallback declarado |
| Autenticação | Laravel Fortify ^1.41, com telas próprias em Blade |
| Banco | PostgreSQL via Docker Compose. MariaDB 11.8 como alternativa. SQLite só até a Fase 1 |
| Cache e filas | Redis em produção; driver `database` em desenvolvimento |
| QR Code | `bacon/bacon-qr-code`, gerado localmente |
| Testes | PHPUnit 12; testes de concorrência sempre em PostgreSQL ou MariaDB, nunca em SQLite |

Sem React ou Vue. Evitar dependências desnecessárias. Docker Compose opcional para app, banco e Redis, com instalação convencional documentada. Decisões técnicas registradas em `docs/ARQUITETURA.md`.

Organização: Services/Actions para domínio; Events/Listeners/Jobs para pontuação, notificações e tarefas demoradas; Policies/Gates e Form Requests em toda operação.

## 2. Identidade visual

Plataforma com linguagem de jogo 8/16-bit, moderna e profissional, sem aspecto de software antigo ou infantil.

**Marca:** logo Se Aprender (livro aberto = aprendizado; estrela 8-bit = conquistas, pontos e evolução).

| Token | Cor | Uso |
| --- | --- | --- |
| Azul | `#004C94` | Marca, fundos, links |
| Laranja | `#F7941D` | Ação principal, destaques |
| Pêssego | `#FBC483` | Apoio, realces |
| Noite | `#071A33` | Fundos escuros, texto sobre laranja |
| Branco/Creme | `#FFFFFF` / `#FFF8EE` | Fundos claros |

**Regra de contraste:** texto sobre laranja é sempre Noite, nunca branco (branco sobre `#F7941D` tem 2,28:1 e reprova). Laranja sobre azul só em texto grande. Laranja como texto em fundo claro usa `#A65300` (5,16:1).

**Tipografia:** Pixelify Sans somente em títulos grandes (o "c" fecha em tamanhos pequenos), Figtree em textos, Fredoka na marca, em botões e abas. Fontes servidas localmente.

**Interface:** navbar/sidebar, breadcrumbs, cards, abas, modais e toasts consistentes; ícones de moeda, XP, estrela, troféu, baú, controle, livro e missão; botões pixelados com feedback de clique; barras de XP e microanimações.

**Texto:** direto e curto, em português do Brasil. Frases que mostram o produto, sem justificar decisões, sem jargão de marketing e sem listas de "não fazemos X".

**Qualidade:** responsivo em 360px, tablet, notebook e modo projetor; WCAG 2.2 AA; `prefers-reduced-motion`; lazy loading; sons retrô opcionais, silenciáveis e nunca automáticos.

## 3. Assets

Guardar em `public/assets/pixel/` com nomes padronizados e WebP quando útil. Registrar fonte, autor, URL, licença e exigências em `docs/ASSET_CREDITS.md`. Nada de hotlink.

Ordem de preferência: CC0 → CC BY com atribuição → avaliar ShareAlike caso a caso. Conferir o arquivo de licença dentro do pacote baixado antes de integrar. Situação atual das fontes em `ASSET_CREDITS.md`. Nunca usar personagens, arte ou marcas de franquias. Se um download exigir login, reportar e usar alternativa licenciada ou arte original.

## 4. Avatares inclusivos

Biblioteca 8-bit em que todo aluno consiga se ver. O usuário escolhe um avatar pronto ou monta o seu. Nunca inferir raça, etnia, gênero ou idade a partir de foto, nome ou cadastro.

Variedade real de: tons de pele; aparências negras, brancas, asiáticas, indígenas e miscigenadas, sem estereótipo; cabelos lisos, ondulados, cacheados, crespos, raspados, curtos e longos, em várias cores; expressões de gênero sem escolha binária; idades de jovens a pessoas mais velhas; óculos, aparelhos auditivos e acessórios de mobilidade.

Editor no perfil com pré-visualização, inventário, troca de cores e persistência. `image-rendering: pixelated`, animações idle/walk e texto alternativo. Aparência nunca afeta custo, nota ou pontuação.

## 5. Autenticação, perfis e turmas

- **Administrador:** usuários, permissões, turmas, auditoria.
- **Professor:** turmas, conteúdos, jogos, recompensas, acompanhamento.
- **Aluno:** atividades, jogos, perfil, XP, moedas, resultados.
- **Convidado de jogo:** entra por QR/PIN/apelido, sem dados privados e sem moedas permanentes.

**Vínculo de convidado:** ao final da partida, o convidado pode entrar na própria conta. Se houver matrícula ativa na turma da sala, os pontos são creditados uma única vez, com a mesma chave de idempotência da partida. Sem matrícula, nada é creditado.

Turma: nome, curso, unidade curricular, período, professor, código de matrícula, datas, status, alunos. Importação CSV validada e convite por código.

**Saldos:** moedas e XP pertencem à matrícula, portanto cada turma tem saldo próprio. Avatar e inventário cosmético pertencem ao usuário. O nível exibido no perfil soma o XP das matrículas.

Recuperação de senha, sessões seguras e limite de tentativas.

## 6. Dashboard do aluno

Home estilo RPG: saudação e avatar; nível, XP, moedas, medalhas; missões do dia e da semana; próximas atividades; avisos da turma; atalhos para Jogar, Atividades, Perfil, Biblioteca, Loja e Portfólio; mapa de aprendizado com mundos, fases e pré-requisitos; evolução por competência com feedback privado. Páginas de perfil, histórico, inventário, conquistas e avatar.

## 7. Painel do professor

Turmas, alunos, entregas pendentes, atividades ativas, jogos, moedas concedidas, resgates e indicadores. Ações rápidas: Nova Atividade, Criar Quiz, Abrir Sala, Registrar Participação, Publicar Aviso, Criar Missão, Avaliar Entregas, Relatórios. Banco de questões reutilizável com filtros, importação/exportação e duplicação entre turmas sem levar dados pessoais.

## 8. Atividades e conteúdos

Tipos: texto, dica, vídeo, link, PDF, imagem, exercício, formulário, múltipla escolha, verdadeiro/falso, envio de trabalho, projeto ZIP, repositório Git e exercício de código.

Campos: título, instruções, anexos, rubrica, unidade curricular, indicadores, disponibilidade, prazo, pré-requisitos, tentativas, XP/moedas, visibilidade e status (rascunho, programada, publicada, encerrada).

Aluno salva rascunho, envia, acompanha avaliação, recebe devolutiva e reenvia quando permitido. Arquivos privados com validação de MIME, extensão e tamanho, download autorizado por Policy, ZIP nunca extraído no servidor, antimalware via adaptador opcional (ClamAV) e histórico de versões.

Biblioteca em módulos, com liberação por data, turma ou pré-requisito.

## 9. Arcade Studio

Editor sem programação, com modelos, preview, publicação e clonagem. Engine extensível: cada tipo implementa contrato comum (validar configuração, gerar payload público sem gabarito, corrigir, pontuar), com DTO, renderizador e eventos próprios.

Ordem de implementação: Pixel Quiz, Verdadeiro ou Falso, Ordene as Etapas (com teclado), Jogo da Memória, Code Quest, Complete o Código, Batalha de Equipes, Roleta do Conhecimento, Escape Room.

- **Complete o Código:** compara texto normalizado com respostas aceitas. Nenhum código é executado.
- **Roleta do Conhecimento:** sorteia tema ou pergunta. Nunca envolve aposta ou custo em moedas.
- Código de usuário nunca roda no servidor principal: sem `eval`, sem `exec`. Sandbox isolado fica para decisão futura.

## 10. Arcade Live

Fluxo mais importante do portal:

1. Professor abre um jogo salvo e clica ABRIR SALA.
2. Sistema gera PIN de 6 dígitos, URL `/j/482916` e QR local.
3. Tela de projetor: logo, título, QR, PIN grande, contagem de jogadores, botão INICIAR.
4. Aluno escaneia o QR, abre o link ou informa o PIN em `/entrar-jogo`.
5. Ingresso por apelido; aluno logado vincula a conta.
6. Professor vê participantes em tempo real e inicia.
7. Pergunta no projetor e no dispositivo; respostas vão ao servidor.
8. Temporizador definido pelo servidor, feedback de rodada e ranking.
9. Fim: pódio, desempenho e crédito idempotente aos alunos habilitados.

**Estados:** lobby, iniciando, jogando, resposta fechada, resultados, finalizado, cancelado, expirado.

**Regras:**

- PIN único entre salas ativas, liberado ao encerrar. Sala expira por inatividade.
- `closes_at` no servidor é a única fonte de verdade; o cliente só exibe a contagem, corrigida pelo relógio do servidor. Tolerância de rede de até 300 ms.
- Gabarito nunca chega ao cliente antes do fechamento da pergunta.
- Uma resposta por jogador por pergunta, garantida por índice único.
- Reconexão por token do jogador, cobrindo aba em segundo plano, conexão lenta, apelido duplicado, limite de participantes e encerramento pelo professor.
- Validação de dono da sala e de matrícula.
- Pontuação com base por acerto e bônus de velocidade limitado, configurável por jogo.

**Rede da escola:** toda a turma pode sair pelo mesmo IP. Rate limit por jogador/sessão e, separadamente, por IP com capacidade para 50 alunos simultâneos. Tentativas de PIN também limitadas por sessão, para impedir varredura sem bloquear a sala.

**Acesso pelo celular:** a URL do QR vem de `APP_URL`. Em produção, HTTPS e `wss://`. Em aula local, servidor em `0.0.0.0` com o IP da máquina na rede. Se WebSocket estiver bloqueado, o cliente troca para polling sozinho.

## 11. Participação, XP, moedas e ranking

Professor marca participação com um clique: pergunta relevante, resposta fundamentada, exercício prático, colaboração, projeto, evolução ou apresentação. Registrar contexto, critério, data, aluno e responsável, com correção auditável. Prever participação escrita e prática para alunos tímidos.

Três coisas separadas:

- **XP:** experiência acumulada, nunca gasta.
- **Moedas:** saldo consumível, com histórico transacional.
- **Avaliação pedagógica:** indicadores, evidências e menções, independentes de XP.

Regras por turma/atividade, com teto diário, antifraude e sem crédito duplicado. Valores iniciais: participação +5, quiz +10, atividade +20, desafio +30, missão semanal +40. Ranking opcional, por apelido, sem expor notas.

## 12. Loja de recompensas

Catálogo com custo, estoque, prazo, elegibilidade, limite por aluno, solicitação, aprovação, rejeição, estorno e histórico. Exemplos: prêmio simbólico, nova tentativa, missão extra, certificado interno, prazo adicional.

Recompensas de +0,5 a +8,0 pontos e dispensa de atividade: cadastráveis, **desabilitadas por padrão**, sem nenhum efeito automático em nota ou aprovação. Habilitar só com autorização formal da coordenação, guardando aprovador e critério em auditoria.

Moedas nunca são vendidas por dinheiro. Sem apostas ou jogos de azar. **Baús** têm conteúdo visível antes de abrir, são ganhos por conquista e nunca comprados com moedas.

## 13. Missões e progressão

Missões diárias, semanais e por módulo, com objetivos configuráveis. Mundos e fases por pré-requisito. Medalhas, conquistas, baús cosméticos, equipes, portfólio com autorização de visibilidade e retrospectiva final do curso. Pontuar persistência, melhora e colaboração, além de acertos.

## 14. Relatórios

Por turma, aluno, atividade, jogo e assunto: entregas, evolução, indicadores, participação, questões com alto erro, moedas/XP e resgates. Exportação CSV/PDF. Engajamento nunca é apresentado como competência.

## 15. IA assistiva (opcional)

Configurável por `.env`; o portal funciona sem credenciais. Sugere quiz, dicas, revisão e feedback a partir do material do professor, que aprova antes de publicar. Não altera menções, não envia dados pessoais ao provedor e não publica gabarito sem revisão.

## 16. Segurança, LGPD e acessibilidade

Autorização por recurso e turma, hash seguro, CSRF, rate limiting, validação no servidor, prevenção de XSS e IDOR, upload privado, logs, backups, auditoria de saldo. Minimização de dados, proteção de menores, retenção e exclusão definidas com a instituição antes de receber dados reais. Sem lista pública de notas ou e-mails. Alternativas para quem não usa smartphone ou animações.

## 17. Dados e testes

Migrations e relações para: usuários, turmas, matrículas, unidades, conteúdos, atividades, entregas, participações, jogos, perguntas, opções, sessões, jogadores, respostas, transações de pontos, missões, medalhas, avatares, inventário, recompensas, resgates, notificações e logs.

Seeders fictícios: professor João, turma de programação, 15 alunos diversos, quiz, atividade, recompensas bloqueadas e avatares.

Testar: login e permissões; escopo de turma; cronograma; upload; participação; criação de quiz; entrada por link/QR/PIN; temporizador; resposta duplicada; reconexão; crédito e débito transacionais; estorno; resgate bloqueado; navegação mobile; contraste; teclado.

**Carga da sala:** 50 jogadores respondendo na mesma janela de 5 s, sem erro 5xx, sem resposta perdida ou duplicada, p95 abaixo de 500 ms.

## 18. Páginas

Landing de apresentação; login, cadastro e convite; dashboard do professor; turmas e alunos; atividades e entregas; biblioteca; Arcade Studio e banco de questões; lobby com QR/PIN e modo projetor; ingresso e jogo do aluno; resultados e pódio; perfil, avatar e inventário; mapa e missões; loja e aprovação; participações rápidas; relatórios; administração; estados de erro, vazio, carregamento e acesso negado.

**Landing:** uma tela só, simples e bonita, para se cadastrar e entrar. Título curto, uma frase sobre o produto e o formulário. Sem demo jogável, sem seções de marketing, sem texto defensivo.

## 19. Entrega

Seguir [ROADMAP.md](ROADMAP.md). README final com ambiente, instalação, credenciais de demonstração, comandos de teste, deploy, Reverb e checklist de produção.

## Critério de aceite principal

Professor real consegue: criar turma → cadastrar aluno → publicar atividade → criar Pixel Quiz → abrir sala → exibir QR/PIN → aluno entrar pelo celular ou PC → responder → ver ranking → ganhar XP/moedas sem duplicação → entregar atividade → receber correção → solicitar recompensa → professor aprovar/rejeitar → consultar histórico.

Ao final, demonstrar esse fluxo com dados fictícios e informar o que foi executado e o que depende de homologação. Detalhes em [CHECKLIST_ACEITE.md](CHECKLIST_ACEITE.md).
