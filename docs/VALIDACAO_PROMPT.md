# Validação do prompt mestre

Revisão feita em 07/11/2026, com testes no ambiente real. O prompt revisado está em [PROMPT_MESTRE.md](PROMPT_MESTRE.md).

## O que foi testado

| Item | Resultado |
| --- | --- |
| Laravel 13 | Instalado: 13.40.0, PHP 8.4.27 |
| Livewire 4 + Reverb + QR local | `composer require --dry-run -W` resolveu: Livewire 4.4.8, Reverb 1.12.0, bacon-qr-code 3.1.1 |
| Tailwind 4 + Vite | Presentes no projeto (Vite 8) |
| `composer run dev` no Windows | Sobe servidor, fila e Vite |
| Licenças Kenney | CC0 confirmado nas páginas do site |
| Licença do Customizable Character Pack | CC0 confirmado na página |
| Licenças do LPC | Não confirmadas: varia por camada e a página não expôs os créditos |
| PostgreSQL, Redis, ClamAV locais | Não instalados |
| Docker | Em uso desde a Fase 1. O `compose.yaml` sobe MySQL e Redis (PostgreSQL até 10/11/2026) |
| MariaDB local | Instalado (11.8.9), não iniciado |

## Conflitos corrigidos

| Problema na versão original | Correção |
| --- | --- |
| "Baús" e "Roleta" conflitam com "sem jogos de azar" | Baú tem conteúdo visível e é ganho por conquista. Roleta só sorteia tema, sem custo |
| Convidado sem moedas, mas com "vínculo de conta para pontos" | Fluxo definido: vincula no fim, com matrícula ativa, crédito único |
| "Não misturar saldos" sem dizer onde o saldo mora | Moedas e XP por matrícula; avatar e inventário por usuário |
| Paleta sem regra de texto | Branco sobre laranja reprova (2,28:1). Regra de contraste adicionada |
| Fonte pixel em tudo | Em tamanho pequeno, "Criar conta" lia como "Oriar oonta". Pixelify fica só em títulos grandes |
| "Não favorecer rápidos" versus ranking por velocidade | Pontos base por acerto e bônus de velocidade limitado e configurável |
| "Complete o Código" com "validar respostas" | Comparação de texto normalizado, sem execução |

## Lacunas preenchidas

- **Definição de pronto** objetiva, no lugar de critérios espalhados.
- **Acesso do celular à sala:** `APP_URL`, HTTPS/`wss://`, uso em rede local e troca automática para polling.
- **PIN:** unicidade entre salas ativas, expiração e limite contra varredura.
- **Timer:** `closes_at` como fonte de verdade e tolerância de 300 ms.
- **Carga:** meta mensurável (50 jogadores, p95 abaixo de 500 ms).
- **Testes de concorrência:** exigem banco real, porque SQLite não reproduz os bloqueios.
- **Upload:** ZIP nunca é extraído; antimalware por adaptador opcional.
- **LGPD:** retenção e exclusão precisam ser definidas com a instituição antes de dados reais.
- **Acessibilidade:** alvo definido como WCAG 2.2 AA.
- **Tom de texto e landing:** regras próprias. A landing passou a ser só cadastro e login, por decisão do João.

## Ordem de execução

A versão original pedia tudo em sequência por camada (todo o backend, depois todo o frontend). O roadmap agora entrega fatias verticais: o critério de aceite principal fica completo na Fase 5, antes de avatares, jogos extras e relatórios.

## Mantido sem alteração

Escopo funcional, perfis, nove jogos, estados da sala, separação entre XP, moedas e avaliação, recompensas de nota bloqueadas, regras de IA e critério de aceite.
