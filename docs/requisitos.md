```md
# Requisitos do Sistema - SISTEMA-GESTAO-AGRO

## 1. Visão geral

O SISTEMA-GESTAO-AGRO é um sistema voltado à gestão agropecuária, com foco no controle do rebanho, acompanhamento sanitário, pesagens, apoio ao gerenciamento financeiro e registro de informações importantes da fazenda.

O sistema terá versão web para administração e versão mobile para uso em campo.

---

## 2. Objetivo geral

Desenvolver um sistema capaz de auxiliar o pecuarista no controle e organização das informações da fazenda, melhorando o acompanhamento dos animais e apoiando a tomada de decisão.

---

## 3. Escopo do MVP

A primeira versão do sistema deverá contemplar:

- cadastro de animais
- listagem de animais
- registro de pesagens
- registro de manejos sanitários
- dashboard básico
- controle financeiro simples

---

## 4. Atores do sistema

### 4.1 Administrador
Usuário responsável por gerenciar os dados gerais do sistema.

### 4.2 Funcionário / operador
Usuário responsável pelo registro de informações do rebanho e eventos em campo.

### 4.3 Gestor / proprietário
Usuário responsável pela consulta de indicadores, relatórios e acompanhamento geral da fazenda.

---

## 5. Requisitos funcionais

### RF01 — Cadastro de animais
O sistema deve permitir cadastrar animais com os seguintes dados:
- brinco
- nome/apelido
- raça
- sexo
- data de nascimento
- lote

### RF02 — Listagem de animais
O sistema deve permitir listar todos os animais cadastrados.

### RF03 — Consulta de animal
O sistema deve permitir visualizar os dados de um animal específico.

### RF04 — Registro de pesagens
O sistema deve permitir registrar pesagens por animal.

### RF05 — Histórico de pesagens
O sistema deve permitir consultar o histórico de pesagens de cada animal.

### RF06 — Registro sanitário
O sistema deve permitir registrar eventos sanitários por animal, como:
- vacina
- vermífugo
- suplemento
- medicação

### RF07 — Alertas sanitários
O sistema deve exibir alertas de vacinas ou manejos pendentes.

### RF08 — Dashboard do rebanho
O sistema deve exibir um resumo com:
- total de animais
- status do rebanho
- evolução de peso
- alertas sanitários

### RF09 — Controle financeiro
O sistema deve permitir registrar receitas e despesas.

### RF10 — Resumo financeiro
O sistema deve exibir um balanço financeiro simples.

### RF11 — Relatórios básicos
O sistema deve permitir gerar relatórios básicos de acompanhamento do rebanho e da gestão financeira.

### RF12 — Aplicativo mobile
O sistema deve possuir uma interface mobile para facilitar o registro de informações em campo.

### RF13 — Operação offline futura
O aplicativo mobile deverá futuramente permitir registro offline com sincronização posterior.

---

## 6. Requisitos não funcionais

### RNF01 — Interface amigável
O sistema deve possuir interface simples e de fácil utilização.

### RNF02 — Persistência dos dados
Os dados devem ser armazenados em banco de dados MariaDB.

### RNF03 — Organização do código
O projeto deve manter separação entre backend, mobile, banco de dados e documentação.

### RNF04 — Desempenho
As operações principais do sistema devem responder de forma adequada ao uso comum.

### RNF05 — Portabilidade
O módulo mobile deve ser desenvolvido em Flutter para possibilitar uso em diferentes plataformas futuramente.

### RNF06 — Confiabilidade
Os dados cadastrados não devem ser perdidos durante o uso normal do sistema.

### RNF07 — Versionamento
O código-fonte deve ser mantido em repositório GitHub para controle de versão e colaboração da equipe.

---

## 7. Regras de negócio

### RN01
Cada animal deve possuir um brinco único.

### RN02
Cada animal deve possuir um identificador interno único no banco de dados.

### RN03
Toda pesagem deve estar vinculada a um animal existente.

### RN04
Todo registro sanitário deve estar vinculado a um animal existente.

### RN05
O sistema não deve permitir duplicidade de brinco.

### RN06
O dashboard deve refletir os dados cadastrados no sistema.

---

## 8. Casos de uso principais

- cadastrar animal
- listar animais
- consultar animal
- registrar pesagem
- consultar histórico de pesagens
- registrar evento sanitário
- visualizar alertas sanitários
- registrar receita
- registrar despesa
- visualizar dashboard

---

## 9. Critérios de aceitação iniciais

### CA01
O cadastro de animal será considerado concluído quando for possível inserir e listar um animal no sistema.

### CA02
O registro de pesagem será considerado concluído quando uma pesagem puder ser associada a um animal e exibida no histórico.

### CA03
O registro sanitário será considerado concluído quando um evento de manejo puder ser salvo e exibido para o animal.

### CA04
O dashboard será considerado concluído quando exibir pelo menos total de animais e alertas básicos.

---

## 10. Considerações finais

O projeto será desenvolvido de forma incremental, começando por um MVP funcional e evoluindo posteriormente para funcionalidades mais completas, incluindo melhorias no aplicativo mobile e sincronização offline.
