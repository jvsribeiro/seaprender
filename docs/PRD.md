# PRD — Se Aprender

## Visão

Uma plataforma em que o professor organiza a turma e o aluno vive a aula como um jogo: atividades, conteúdos e entregas num só lugar, com partidas ao vivo por QR/PIN e uma jornada de XP, moedas e conquistas.

## Problema

- Materiais, entregas, quizzes e pontuação ficam em ferramentas separadas.
- Abrir uma dinâmica em sala toma tempo de aula.
- Participação em sala raramente vira registro.
- Alunos tímidos têm pouca forma de mostrar evolução.

## Público

| Perfil | Necessidade principal |
| --- | --- |
| Professor | Preparar, aplicar e acompanhar com pouco atrito |
| Aluno | Saber o que fazer, ver progresso, jogar, personalizar |
| Convidado | Entrar numa partida em segundos |
| Administrador | Gerir usuários, turmas e auditoria |

Contexto inicial: turmas de tecnologia em Bebedouro/SP, de 15 a 50 alunos, rede compartilhada, projetor e celulares variados, com alunos que podem ser menores de idade.

## Objetivos e métricas

| Objetivo | Como medir |
| --- | --- |
| Abrir sala rapidamente | Do clique em ABRIR SALA ao QR no projetor em menos de 5 s |
| Turma inteira entra | 95% dos alunos no lobby em até 60 s |
| Partida estável | 50 jogadores, p95 abaixo de 500 ms, zero resposta perdida |
| Pontos confiáveis | Zero crédito duplicado em testes e auditoria |
| Uso contínuo | Atividades publicadas e entregas registradas por turma |

## Escopo do MVP

Tudo o que o critério de aceite exige:

1. Login, perfis, turmas, catálogo com inscrição do aluno, matrículas por código e CSV.
2. Atividades com entrega, correção e devolutiva.
3. Banco de questões e Pixel Quiz.
4. Arcade Live completo com QR, PIN, ranking e pódio.
5. XP, moedas, participação rápida, loja com aprovação e histórico.

## Depois do MVP

Avatares completos, oito jogos adicionais, missões, mapa, portfólio, biblioteca avançada, relatórios, IA assistiva.

## Fora do escopo

- Pagamentos ou venda de moedas.
- Alteração automática de notas.
- Execução de código enviado por usuários.
- Aplicativo nativo.
- Integração com sistemas acadêmicos institucionais.

## Requisitos não funcionais

- WCAG 2.2 AA, teclado, `prefers-reduced-motion`.
- Layout a partir de 360px e modo projetor.
- LGPD: minimização, autorização por recurso, retenção definida.
- Funciona sem IA e sem WebSocket (fallback por polling).

## Riscos

| Risco | Resposta |
| --- | --- |
| Rede da escola bloqueia WebSocket | Fallback automático por polling, testar na rede real cedo |
| NAT bloqueia a turma por rate limit | Limites separados por jogador e IP |
| Dados de menores | Coleta mínima e validação com a instituição antes de uso real |
| Escopo muito grande | Fatias verticais, MVP fechado na Fase 5 |
| Hospedagem compartilhada não roda Reverb | Exigir VPS ou equivalente |
| Gamificação substituir avaliação | XP, moedas e menções em domínios separados |

## Decisões pendentes

Listadas em [ROADMAP.md](ROADMAP.md#decisões-pendentes).
