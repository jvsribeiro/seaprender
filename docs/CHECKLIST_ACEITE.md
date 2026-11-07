# Checklist de aceite

## Fluxo principal

Executar com dados fictícios, em sessões distintas para professor e alunos.

- [ ] Professor cria turma
- [ ] Professor cadastra aluno (código ou CSV)
- [ ] Professor publica atividade
- [ ] Professor cria Pixel Quiz
- [ ] Professor abre sala
- [ ] Projetor exibe QR, PIN e link
- [ ] Aluno entra pelo celular
- [ ] Aluno entra pelo PC
- [ ] Alunos respondem às perguntas
- [ ] Ranking e pódio aparecem
- [ ] XP e moedas creditados uma única vez
- [ ] Aluno entrega atividade
- [ ] Professor corrige e dá devolutiva
- [ ] Aluno solicita recompensa válida
- [ ] Professor aprova
- [ ] Professor rejeita, com estorno
- [ ] Histórico completo consultável

## Arcade Live

- [ ] Gabarito ausente no payload da pergunta aberta
- [ ] Resposta depois do fechamento é recusada
- [ ] Segunda resposta à mesma pergunta é ignorada
- [ ] Reconexão mantém pontuação
- [ ] Apelido duplicado tratado
- [ ] Limite de participantes respeitado
- [ ] Fallback por polling funciona com WebSocket desligado
- [ ] 50 jogadores na mesma rede sem bloqueio por IP
- [ ] Carga: sem 5xx, sem resposta perdida, p95 abaixo de 500 ms
- [ ] Encerrar e expirar sala liberam o PIN

## Economia

- [ ] Crédito simultâneo não duplica
- [ ] Débito simultâneo não deixa saldo negativo
- [ ] Estorno gera nova transação
- [ ] Teto diário aplicado
- [ ] Saldos de turmas diferentes não se misturam
- [ ] Recompensa de nota bloqueada por padrão

## Segurança e privacidade

- [ ] Aluno não acessa dados de outra turma
- [ ] Professor não acessa turma alheia
- [ ] Download de arquivo exige autorização
- [ ] Upload recusa tipo não permitido
- [ ] Sem notas ou e-mails em telas públicas
- [ ] Alterações de saldo auditadas

## Interface

- [ ] Layout correto em 360px, tablet, notebook e projetor
- [ ] Fluxos principais por teclado
- [ ] Contraste AA
- [ ] `prefers-reduced-motion` respeitado
- [ ] Som nunca toca sozinho
- [ ] Estados vazio, carregando, erro, sucesso e reconexão

## Depende de homologação

- [ ] Teste na rede real da escola
- [ ] Teste com turma real
- [ ] Política LGPD aprovada pela instituição
- [ ] Autorização formal para recompensas ligadas a nota
