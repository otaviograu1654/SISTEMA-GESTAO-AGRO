# Roadmap do Projeto

## Objetivo
Organizar a evolucao do sistema em fases pequenas para concluir o painel do gestor primeiro, depois separar trabalhador e suporte.

## Visao das telas
- Painel atual: gestor/admin da fazenda
- Painel futuro: trabalhador com acesso por modulos liberados pelo gestor
- Painel futuro: desenvolvedor/suporte para responder chamados

## Fase 1 - Estabilizar o que ja foi feito
Status: concluida

Feito:
- testes de contas a pagar
- testes de pagar conta
- testes de excluir conta
- testes de venda de animal
- testes de integracao da venda com financeiro
- testes de status do animal

## Fase 2 - Organizar melhor o financeiro atual
Status: concluida

Objetivo:
- deixar claro o papel de cada pagina financeira

Paginas:
- `backend/contas_a_pagar.php`
- `backend/lancamentos_vista.php`
- `backend/compras.php`
- `backend/vendas.php`
- `backend/fluxo_caixa.php`

## Fase 3 - Ajustar visual e comportamento dos status dos animais
Status: concluida

Objetivo:
- deixar mais claro quando o animal esta ativo, vendido ou em obito
- decidir se botoes de baixa devem ficar escondidos ou so bloqueados

Decisao:
- botoes de venda e obito ficam visiveis, porem bloqueados quando o animal ja esta vendido ou em obito
- a tela mostra um aviso com a situacao atual do animal antes das acoes de baixa

## Fase 4 - Criar cadastro de parceiros
Status: concluida

Objetivo:
- cadastrar comprador, fornecedor, cliente e prestador

Feito:
- criada tabela `parceiros`
- criada tela `backend/parceiros.php`
- adicionado link de parceiros no menu principal
- validado cadastro web de parceiro com registro de teste

## Fase 5 - Integrar parceiros na venda de animal
Status: concluida

Objetivo:
- trocar comprador digitado por selecao de parceiro

Feito:
- adicionada coluna `parceiro_id` em `animal_vendas`
- formulario de venda passa a selecionar parceiro do tipo comprador
- `comprador_nome` continua sendo preenchido para manter compatibilidade com vendas antigas
- validada venda pela interface web usando parceiro comprador

## Fase 6 - Integrar parceiros em compras
Status: concluida

Objetivo:
- trocar fornecedor digitado por selecao de parceiro

Feito:
- adicionada coluna `parceiro_id` em `financeiro`
- `backend/financeiro.php` passa a aceitar e retornar parceiro vinculado
- `backend/compras.php` passa a selecionar parceiro do tipo fornecedor
- validado lancamento de compra com fornecedor pela interface/API web

## Fase 7 - Criar cadastro de racas
Status: concluida

Feito:
- criada tabela `racas`
- criada tela `backend/racas.php`
- adicionado link de racas no menu principal
- validado cadastro web de raca com registro de teste

## Fase 8 - Criar cadastro de lotes
Status: concluida

Feito:
- criada tabela `lotes`
- criada tela `backend/lotes.php`
- adicionado link de lotes no menu principal
- validado cadastro web de lote com registro de teste

## Fase 9 - Integrar racas e lotes nos animais
Status: concluida

Objetivo:
- trocar campos de texto por selecao no cadastro/edicao de animal

Feito:
- cadastro de animal passou a selecionar raca ativa
- cadastro de animal passou a selecionar lote ativo
- edicao de animal passou a selecionar lote ativo
- raca permanece bloqueada na edicao, mas exibida em selecao para preservar a regra atual da tela
- validado cadastro e edicao de animal pela interface web com raca/lote de teste

## Fase 10 - Transformar estoque em modulo real
Status: concluida

Objetivo:
- sair da tela visual e registrar produtos de verdade

Feito:
- criada tabela `estoque_produtos`
- tela `backend/estoque.php` passou a cadastrar produtos no banco
- listagem e cards do estoque passaram a refletir dados reais
- validado cadastro web de produto com registro de teste

## Fase 11 - Criar movimentacao de estoque
Status: concluida

Objetivo:
- entradas, saidas, historico e alertas

Feito:
- criada tabela `estoque_movimentacoes`
- tela `backend/estoque.php` passou a registrar entradas e saidas
- movimentacoes atualizam `quantidade_atual` em `estoque_produtos`
- saida maior que saldo disponivel e bloqueada
- historico de movimentacoes aparece na tela de estoque
- alertas basicos de vencimento e estoque zerado foram adicionados
- validado fluxo web de entrada e saida com produto de teste

## Fase 12 - Criar producao/pesagem de leite
Status: concluida

Objetivo:
- registrar producao diaria

Feito:
- criada tabela `producao_leite`
- criada tela `backend/producao_leite.php`
- adicionado link no submenu de movimentacao
- producao pode ser geral do tanque ou vinculada a uma femea ativa
- cards e historico exibem dados reais do banco
- validado registro web de producao com dado de teste

## Fase 13 - Criar login e autenticacao
Status: concluida

Objetivo:
- proteger o sistema com sessao

Feito:
- criada tela `backend/login.php`
- criado `backend/logout.php`
- criado `backend/includes/auth.php`
- paginas internas que usam layout passaram a exigir sessao
- endpoints e acoes principais passaram a exigir sessao
- criado usuario inicial `admin@sga.local` / `admin123` quando necessario
- validado login, acesso protegido, endpoint autenticado e logout via XAMPP

## Fase 14 - Criar perfis e permissoes por modulo
Status: concluida

Objetivo:
- gestor cadastra funcionario
- gestor escolhe quais modulos cada funcionario acessa
- desenvolvedor cadastra fazendeiros e tambem funcionarios
- fazendeiro cadastra e controla os seus funcionarios
- usuarios, lotes e parceiros podem ser desativados sem perder historico

Feito:
- usuario inicial `admin@sga.local` passou a representar o perfil `Desenvolvedor`
- criada hierarquia basica: Desenvolvedor, Fazendeiro e Funcionario
- `backend/usuarios.php` passou a cadastrar usuarios conforme a hierarquia
- usuarios podem ser ativados, desativados ou excluidos
- fazendeiro so altera funcionarios criados por ele
- botao de ativar/desativar adicionado em lotes
- botao de ativar/desativar adicionado em parceiros
- criada tabela `usuario_permissoes`
- cadastro de usuario permite escolher modulos do funcionario
- listagem de usuarios permite atualizar permissoes do funcionario
- menu esconde modulos sem permissao
- paginas e endpoints principais bloqueiam acesso direto sem permissao
- validado funcionario com acesso apenas a cadastros e bloqueio no financeiro

## Fase 15 - Criar painel de suporte/desenvolvedor
Status: pendente

Objetivo:
- separar atendimento tecnico do painel da fazenda

## Fase 16 - Editar usuarios e redefinir senha
Status: pendente

Objetivo:
- permitir editar nome, email, perfil e status de usuarios
- permitir redefinir senha de fazendeiros e funcionarios
- evitar que o ultimo desenvolvedor seja removido ou bloqueado

## Fase 17 - Filtros avancados no rebanho
Status: pendente

Objetivo:
- buscar animais por brinco, nome, sexo, raca, lote e status
- facilitar consultas para apresentacao e uso diario
- criar filtros combinados na listagem de animais

## Fase 18 - Relatorios em PDF
Status: pendente

Objetivo:
- gerar relatorios de animais, financeiro, estoque e producao de leite
- permitir impressao ou entrega formal dos dados
- criar layout simples e profissional para relatorios

## Fase 19 - Exportacao CSV/Excel
Status: pendente

Objetivo:
- exportar listagens para planilha
- incluir animais, financeiro, estoque e producao
- facilitar conferencia fora do sistema

## Fase 20 - Historico completo do animal
Status: pendente

Objetivo:
- montar linha do tempo do animal
- reunir cadastro, pesagens, vacinas, venda, obito e producao de leite
- deixar a tela do animal mais completa para consulta

## Fase 21 - Alertas inteligentes
Status: pendente

Objetivo:
- alertar vacinas vencendo
- alertar estoque baixo ou validade proxima
- alertar contas a pagar proximas do vencimento

## Fase 22 - Melhorias visuais e mobile
Status: pendente

Objetivo:
- melhorar responsividade no celular
- ajustar menus, tabelas e formularios em telas menores
- deixar a interface mais apresentavel para banca/professor

## Fase 23 - Backup do banco de dados
Status: pendente

Objetivo:
- criar rotina simples de backup
- permitir exportar dados principais
- reduzir risco de perda durante testes e apresentacoes

## Fase 24 - Dashboards por tipo de usuario
Status: pendente

Objetivo:
- painel do desenvolvedor com suporte e fazendas
- painel do fazendeiro com resumo da fazenda
- painel do funcionario com apenas tarefas liberadas

## Fase 25 - Auditoria e rastreabilidade
Status: pendente

Objetivo:
- registrar quem criou, editou ou desativou registros importantes
- acompanhar alteracoes em usuarios, animais, financeiro e estoque
- melhorar seguranca e confiabilidade do sistema

## Proximo passo pratico
- abrir a fase 15
