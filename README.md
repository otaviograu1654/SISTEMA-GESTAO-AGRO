# SISTEMA-GESTAO-AGRO

Sistema de gestão agropecuária desenvolvido para o Projeto Integrador, com foco no controle do rebanho, manejo sanitário, pesagens, acompanhamento zootécnico e apoio à gestão financeira.

## Objetivo

Desenvolver um sistema para auxiliar o pecuarista no registro, organização e acompanhamento de informações importantes da fazenda, reduzindo o uso de anotações dispersas e facilitando a tomada de decisão.

## Problema

Em muitas propriedades rurais, informações sobre animais, vacinação, pesagens, despesas e produção ficam espalhadas em papel, cadernos ou mensagens, dificultando o controle do rebanho e a gestão da fazenda.

## Proposta da solução

O projeto propõe um sistema com duas frentes:

- **Web**: painel administrativo para consulta, controle e acompanhamento geral
- **Mobile**: aplicativo para registro em campo, com foco em uso prático pelo pecuarista

## Funcionalidades principais
-
                    ## Cadastro e identificação ##
- Cadastro de individual de animais
- Historico familiar
- Listagem e consulta de animais
- Registro de pesagens
-
                    ## Saúde e ciclo de vida ##
- Controle de vacinação
- Alerta de vacinação pendente(lembrete para o pecuarista)
- Lembrete de dar algum remedio
- Acompanhamento geral
- Registro de manejos sanitários
- Registro de natalidade e mortalidade
- Registro de animal no ciclo de cio
- Registro de animal prenho e demais etapas ate a concepção 
- Registro de animal em tratamento(deu um antibiotico e portanto em um certo periodo não posso abater o animal nem colocar o leite dele no tanque)
- Dashboard com resumo do rebanho
-
                    ## Gestão de estoque e Insumos ##
- Gestão de estoque (qtd atual, qtd que preciso para a semana/mes, estoque de remedios atual em estoque e quais preciso comprar)
- Historico de movimentação
- Lembretes (produto acabando, proximos do vencimento, estoque acabando)
- Controle de validade
- Quantidade de insumos produzidos(leite, ovos, varia de acordo com o ramo do pecuarista)
-
                    ## Controle Financeiro ##
- Controle financeiro básico
- Controle de vendas e gastos (registro de receitas e despesas)
- Valor patrimonial (terras, rebanho, bens materiais, se o pecuarista achar necessário)
- Anexar contas a pagar, a receber
- Controle de lucro ou prejuizo
- Relatórios geral de produtividade
- Historico financeiro
- Dashboard com graficos

## Funcionalidaades mais complexas
- Rastreamento de animais pelo brinco
- Um mini sistema de cálculo automatico de acordo com insumo produzido(valor atual do leite, valor atual do ovo, valor atual da arroba)
- Analise de custo para engordar um bezerro
- Funcionamento offline mobile (sincronização de dados quando online)
- Suporte futuro

## Tecnologias utilizadas

### Backend
- PHP
- MariaDB

### Mobile
- Flutter
- Dart

### Banco de dados
- MariaDB

### Versionamento
- Git
- GitHub

## Estrutura do repositório

```text
mobile/
backend/
database/
docs/
README.md
