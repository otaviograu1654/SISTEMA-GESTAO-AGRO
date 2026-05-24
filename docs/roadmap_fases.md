# Roadmap do Projeto

## Objetivo
Organizar a evolução do sistema em fases pequenas para concluir primeiro o painel principal da fazenda, depois separar a experiência do funcionário e, por fim, criar o painel interno do desenvolvedor/suporte.

## Visão das telas
- Painel atual: admin/gestor da fazenda
- Painel futuro: funcionário com acesso por módulos liberados pelo gestor
- Painel futuro: desenvolvedor/suporte para responder chamados e acompanhar clientes

## Situação atual resumida
- A base do painel do gestor já existe e está funcional em boa parte do projeto
- Login, permissões básicas, parceiros, raças, lotes, estoque e produção de leite já existem no código
- A parte financeira ainda está funcional, mas não totalmente consolidada
- O fluxo de caixa ainda está como protótipo
- O painel do funcionário ainda não está fechado como experiência final
- O painel do desenvolvedor ainda está pendente

## Fase 1 - Estabilizar o que já foi feito
Status: concluída

Feito:
- testes de contas a pagar
- testes de pagar conta
- testes de excluir conta
- testes de venda de animal
- testes de integração da venda com financeiro
- testes de status do animal

## Fase 2 - Organizar melhor o financeiro atual
Status: concluída

Objetivo:
- deixar claro o papel de cada página financeira

Páginas:
- `backend/contas_a_pagar.php`
- `backend/lancamentos_vista.php`
- `backend/compras.php`
- `backend/vendas.php`
- `backend/fluxo_caixa.php`

## Fase 3 - Ajustar visual e comportamento dos status dos animais
Status: concluída

Objetivo:
- deixar mais claro quando o animal está ativo, vendido ou em óbito
- decidir se botões de baixa devem ficar escondidos ou só bloqueados

Decisão:
- botões de venda e óbito ficam visíveis, porém bloqueados quando o animal já está vendido ou em óbito
- a tela mostra um aviso com a situação atual do animal antes das ações de baixa

## Fase 4 - Criar cadastro de parceiros
Status: concluída

Objetivo:
- cadastrar comprador, fornecedor, cliente e prestador

Feito:
- criada tabela `parceiros`
- criada tela `backend/parceiros.php`
- adicionado link de parceiros no menu principal
- validado cadastro web de parceiro com registro de teste

## Fase 5 - Integrar parceiros na venda de animal
Status: concluída

Objetivo:
- trocar comprador digitado por seleção de parceiro

Feito:
- adicionada coluna `parceiro_id` em `animal_vendas`
- formulário de venda passa a selecionar parceiro do tipo comprador
- `comprador_nome` continua sendo preenchido para manter compatibilidade com vendas antigas
- validada venda pela interface web usando parceiro comprador

## Fase 6 - Integrar parceiros em compras
Status: concluída

Objetivo:
- trocar fornecedor digitado por seleção de parceiro

Feito:
- adicionada coluna `parceiro_id` em `financeiro`
- `backend/financeiro.php` passa a aceitar e retornar parceiro vinculado
- `backend/compras.php` passa a selecionar parceiro do tipo fornecedor
- validado lançamento de compra com fornecedor pela interface/API web

## Fase 7 - Criar cadastro de raças
Status: concluída

Feito:
- criada tabela `racas`
- criada tela `backend/racas.php`
- adicionado link de raças no menu principal
- validado cadastro web de raça com registro de teste

## Fase 8 - Criar cadastro de lotes
Status: concluída

Feito:
- criada tabela `lotes`
- criada tela `backend/lotes.php`
- adicionado link de lotes no menu principal
- validado cadastro web de lote com registro de teste

## Fase 9 - Integrar raças e lotes nos animais
Status: concluída

Objetivo:
- trocar campos de texto por seleção no cadastro/edição de animal

Feito:
- cadastro de animal passou a selecionar raça ativa
- cadastro de animal passou a selecionar lote ativo
- edição de animal passou a selecionar lote ativo
- raça permanece bloqueada na edição, mas exibida em seleção para preservar a regra atual da tela
- validado cadastro e edição de animal pela interface web com raça/lote de teste

## Fase 10 - Transformar estoque em módulo real
Status: concluída

Objetivo:
- sair da tela visual e registrar produtos de verdade

Feito:
- criada tabela `estoque_produtos`
- tela `backend/estoque.php` passou a cadastrar produtos no banco
- listagem e cards do estoque passaram a refletir dados reais
- validado cadastro web de produto com registro de teste

## Fase 11 - Criar movimentação de estoque
Status: concluída

Objetivo:
- entradas, saídas, histórico e alertas

Feito:
- criada tabela `estoque_movimentacoes`
- tela `backend/estoque.php` passou a registrar entradas e saídas
- movimentações atualizam `quantidade_atual` em `estoque_produtos`
- saída maior que saldo disponível é bloqueada
- histórico de movimentações aparece na tela de estoque
- alertas básicos de vencimento e estoque zerado foram adicionados
- validado fluxo web de entrada e saída com produto de teste

## Fase 12 - Criar produção/pesagem de leite
Status: concluída

Objetivo:
- registrar produção diária

Feito:
- criada tabela `producao_leite`
- criada tela `backend/producao_leite.php`
- adicionado link no submenu de movimentação
- produção pode ser geral do tanque ou vinculada a uma fêmea ativa
- cards e histórico exibem dados reais do banco
- validado registro web de produção com dado de teste

## Fase 13 - Criar login e autenticação
Status: concluída

Objetivo:
- proteger o sistema com sessão

Feito:
- criada tela `backend/login.php`
- criado `backend/logout.php`
- criado `backend/includes/auth.php`
- páginas internas que usam layout passaram a exigir sessão
- endpoints e ações principais passaram a exigir sessão
- criado usuário inicial `admin@sga.local` / `admin123` quando necessário
- validado login, acesso protegido, endpoint autenticado e logout via XAMPP

## Fase 14 - Criar perfis e permissões por módulo
Status: concluída

Objetivo:
- gestor cadastra funcionário
- gestor escolhe quais módulos cada funcionário acessa
- desenvolvedor cadastra fazendeiros e também funcionários
- fazendeiro cadastra e controla os seus funcionários
- usuários, lotes e parceiros podem ser desativados sem perder histórico

Feito:
- usuário inicial `admin@sga.local` passou a representar o perfil `Desenvolvedor`
- criada hierarquia básica: Desenvolvedor, Fazendeiro e Funcionário
- `backend/usuarios.php` passou a cadastrar usuários conforme a hierarquia
- usuários podem ser ativados, desativados ou excluídos
- fazendeiro só altera funcionários criados por ele
- botão de ativar/desativar adicionado em lotes
- botão de ativar/desativar adicionado em parceiros
- criada tabela `usuario_permissoes`
- cadastro de usuário permite escolher módulos do funcionário
- listagem de usuários permite atualizar permissões do funcionário
- menu esconde módulos sem permissão
- páginas e endpoints principais bloqueiam acesso direto sem permissão
- validado funcionário com acesso apenas a cadastros e bloqueio no financeiro

## Fase 15 - Consolidar o financeiro do painel do gestor
Status: concluída

Objetivo:
- fechar a sincronização real da parte financeira
- transformar o fluxo de caixa em leitura consolidada do sistema
- padronizar melhor a origem dos lançamentos financeiros

Fazer:
- integrar `fluxo_caixa.php` com dados reais de `financeiro`
- revisar categorias e descrições de compras, vendas, contas pagas e venda de animais
- confirmar se todas as entradas e saídas importantes caem no mesmo histórico financeiro
- decidir como exibir o extrato geral do caixa para o gestor

Feito:
- `backend/fluxo_caixa.php` passou a ler os dados reais da tabela `financeiro`
- o fluxo de caixa agora salva lançamentos manuais no mesmo histórico financeiro
- criada coluna `origem` em `financeiro` para padronizar a origem dos lançamentos
- compras, vendas, contas pagas, venda de animais e lançamentos à vista passaram a informar origem
- extrato geral do caixa passou a exibir tipo, origem, categoria, descrição, parceiro, valor e saldo acumulado

## Fase 16 - Terminar a tela do gestor da fazenda
Status: concluída

Objetivo:
- fechar o painel principal do gestor/admin para uso completo da fazenda
- melhorar consulta, controle e visão geral dos módulos principais

Fazer:
- editar usuários e redefinir senha
- criar filtros avançados no rebanho
- montar histórico completo do animal
- refinar o dashboard do gestor da fazenda

Feito:
- `backend/usuarios.php` passou a permitir editar nome, email, perfil e status de usuários permitidos pela hierarquia
- `backend/usuarios.php` passou a permitir redefinir senha de usuários gerenciáveis
- mantida a trava para não desativar/remover o último desenvolvedor ativo
- `backend/animais.php` ganhou filtros combinados por busca, sexo, raça, lote e status
- `backend/animal.php` passou a exibir histórico do animal com auditoria, pesagens, manejos, produção de leite, venda e óbito
- `backend/dashboard.php` ganhou indicadores de animais ativos, vendidos, óbitos, produção de leite e origem dos lançamentos recentes

## Fase 17 - Terminar a tela do funcionário
Status: concluída

Objetivo:
- fechar a experiência do funcionário cadastrado pelo gestor
- mostrar só os módulos liberados
- simplificar navegação e tarefas do dia a dia

Fazer:
- revisar dashboard próprio do funcionário
- esconder cards, atalhos e seções sem permissão
- ajustar experiência de uso focada em operação de campo
- garantir bloqueio real de acesso aos módulos não liberados

Feito:
- `backend/dashboard.php` passou a mostrar título e resumo próprios para funcionário
- atalhos do dashboard passaram a aparecer somente quando o módulo correspondente está liberado
- cards e seções de cadastros, movimentação, estoque e financeiro passaram a respeitar permissões
- funcionário com acesso apenas a cadastros não vê financeiro nem movimentação no dashboard
- acesso direto a módulo não liberado continua bloqueado com resposta 403
- validado com usuário de teste `funcionario_fase17@sga.local` com permissão apenas em cadastros

## Fase 18 - Fazer a tela do desenvolvedor / suporte
Status: concluída

Objetivo:
- separar atendimento técnico do painel da fazenda
- criar uma área interna para a equipe de desenvolvimento/suporte

Fazer:
- criar painel interno para responder chamados
- acompanhar status dos chamados
- visualizar fazendas, usuários e contexto de suporte
- evitar misturar suporte técnico com o painel do gestor

Feito:
- `backend/suporte.php` passou a separar a visão comum de abertura de chamado da visão interna do desenvolvedor
- desenvolvedor agora visualiza painel interno com resumo de chamados e usuários
- chamados passaram a ter status `Aberto`, `Em atendimento`, `Respondido` e `Fechado`
- desenvolvedor pode registrar resposta e atualizar status dos chamados
- painel interno exibe contexto de usuários, perfis, status e criador do acesso
- `database/schema.sql` e `backend/setup.php` passaram a incluir resposta e dados de atendimento dos chamados
- validado funcionário abrindo chamado e desenvolvedor respondendo/alterando status

## Fase 19 - Editar usuários e redefinir senha
Status: concluída

Objetivo:
- permitir editar nome, email, perfil e status de usuários
- permitir redefinir senha de fazendeiros e funcionários
- evitar que o último desenvolvedor seja removido ou bloqueado

Feito:
- `backend/usuarios.php` permite editar nome, email, perfil e status de usuários gerenciáveis
- `backend/usuarios.php` permite redefinir senha de fazendeiros e funcionários
- redefinição de senha foi limitada ao escopo de fazendeiros e funcionários
- o sistema impede alterar o próprio acesso ativo do desenvolvedor pela listagem
- a trava contra remoção/desativação do último desenvolvedor ativo permanece aplicada
- validado edição e redefinição com o usuário `funcionario_fase17@sga.local`

## Fase 20 - Filtros avançados no rebanho
Status: pendente

Objetivo:
- buscar animais por brinco, nome, sexo, raça, lote e status
- facilitar consultas para apresentação e uso diário
- criar filtros combinados na listagem de animais

## Fase 21 - Histórico completo do animal
Status: pendente

Objetivo:
- montar linha do tempo do animal
- reunir cadastro, pesagens, vacinas, manejo, venda, óbito e produção de leite
- deixar a tela do animal mais completa para consulta

## Fase 22 - Dashboard do gestor da fazenda
Status: concluída

Objetivo:
- refinar o painel principal do gestor
- exibir visão mais clara de rebanho, financeiro, estoque e produção
- deixar o painel mais próximo da versão final vendável

Feito:
- `backend/dashboard.php` ganhou visão executiva para gestor/fazendeiro
- adicionados indicadores de rebanho ativo, resultado financeiro, produção de leite do dia e alertas prioritários
- criados alertas do gestor para contas atrasadas, vacinação atrasada, estoque zerado e validade próxima
- dashboard passou a exibir produtos em atenção no estoque
- mantida a separação do dashboard do funcionário por permissões
- validado acesso autenticado do gestor com a nova visão executiva

## Fase 23 - Relatórios em PDF
Status: pendente

Objetivo:
- gerar relatórios de animais, financeiro, estoque e produção de leite
- permitir impressão ou entrega formal dos dados
- criar layout simples e profissional para relatórios

## Fase 24 - Exportação CSV/Excel
Status: pendente

Objetivo:
- exportar listagens para planilha
- incluir animais, financeiro, estoque e produção
- facilitar conferência fora do sistema

## Fase 25 - Alertas inteligentes e dashboards por tipo de usuário
Status: concluída

Objetivo:
- alertar vacinas vencendo
- alertar estoque baixo ou validade próxima
- alertar contas a pagar próximas do vencimento
- organizar melhor a visão do gestor, do funcionário e do desenvolvedor

Feito:
- `backend/dashboard.php` passou a montar alertas inteligentes conforme perfil e permissões
- gestor vê alertas consolidados de financeiro, sanidade, estoque e suporte quando aplicável
- funcionário vê somente alertas dos módulos liberados para ele
- desenvolvedor recebe alerta de chamados abertos no dashboard
- alertas incluem contas atrasadas, contas vencendo, vacinações atrasadas, vacinações próximas, estoque zerado e validade próxima
- alertas exibem nível de prioridade e link direto para o módulo correspondente
- validado dashboard do gestor e dashboard do funcionário com permissões restritas

## Fase 26 - Backup, auditoria e segurança base
Status: pendente

Objetivo:
- criar rotina simples de backup
- registrar quem criou, editou ou desativou registros importantes
- reforçar segurança e confiabilidade para apresentação e uso real

## Fase 27 - Polimento visual e responsividade
Status: concluída

Objetivo:
- melhorar a experiência no celular, tablet e desktop
- revisar menus, tabelas, formulários, cards e espaçamentos
- corrigir textos quebrados, acentuação e inconsistências visuais
- deixar o sistema com aparência mais profissional para venda e demonstração

Fazer:
- revisar responsividade das principais telas do gestor, funcionário e desenvolvedor
- melhorar tabelas em telas pequenas
- ajustar botões, formulários inline e espaçamentos
- padronizar mensagens, títulos e labels
- revisar contraste, estados vazios e feedbacks de sucesso/erro

Feito:
- `backend/styles.css` recebeu ajustes globais de responsividade para telas médias e pequenas
- tabelas passaram a ter contorno, rolagem horizontal mais clara e largura mínima controlada
- formulários inline, botões e ações passam a empilhar melhor no celular
- cards, painéis, grids e dashboard receberam ajustes de espaçamento e legibilidade
- menu lateral, topo e conteúdo principal foram ajustados para tablet/celular
- corrigidos rótulos globais do layout/menu com acentuação em `layout.php` e `menu.php`
- validado carregamento de dashboard, usuários, animais e suporte com status 200

## Fase 28 - Recuperação de senha
Status: pendente

Objetivo:
- criar fluxo seguro de "esqueci minha senha"
- permitir que fazendeiros e funcionários recuperem acesso sem depender sempre do desenvolvedor
- manter segurança contra redefinições indevidas

Fazer:
- criar tela de solicitação de recuperação de senha
- gerar token temporário de redefinição
- criar tabela para tokens de recuperação
- criar tela para definir nova senha usando token válido
- expirar tokens usados ou vencidos
- decidir envio por email real ou fluxo manual enquanto estiver em ambiente local

## Fase 29 - Sincronização offline/mobile
Status: pendente

Objetivo:
- permitir registrar informações sem internet no campo
- sincronizar automaticamente quando o dispositivo voltar a ter conexão
- evitar perda de dados e conflitos entre registros locais e servidor

Fazer:
- definir quais módulos terão uso offline primeiro, como animais, pesagens, vacinação, manejo e produção de leite
- criar fila local no app mobile para registros pendentes de envio
- criar endpoints de sincronização no backend
- registrar identificadores únicos dos registros criados offline
- marcar status de sincronização: pendente, enviado, confirmado e erro
- tratar conflitos quando o mesmo registro for alterado no servidor e no dispositivo
- exibir ao usuário quando há dados aguardando sincronização

## Fase 30 - Separação real por fazenda/cliente
Status: pendente

Objetivo:
- preparar o sistema para venda a mais de uma fazenda
- garantir que cada fazendeiro veja somente seus próprios dados
- separar animais, estoque, financeiro, produção, usuários e chamados por fazenda/cliente

Fazer:
- criar tabela de fazendas/clientes
- vincular usuários a uma fazenda
- vincular dados principais à fazenda correta
- ajustar permissões e consultas para filtrar por fazenda
- permitir ao desenvolvedor visualizar clientes no painel interno de suporte

## Ordem de prioridade a partir de agora
1. Alertas inteligentes e dashboards por tipo de usuário
2. Backup, auditoria e segurança base
3. Polimento visual e responsividade
4. Recuperação de senha
5. Relatórios em PDF e exportação CSV/Excel
6. Separação real por fazenda/cliente
7. Sincronização offline/mobile

## Próximo passo prático
- abrir a fase 25
- depois seguir para a fase 26
- depois seguir para a fase 27
- depois seguir para a fase 28
