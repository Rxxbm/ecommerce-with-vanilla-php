# 🛒 Portal de Produtos — Sistema de E-commerce

Este é um sistema de e-commerce desenvolvido com PHP e MySQL, que permite o cadastro, exibição, compra e gerenciamento de produtos online. A aplicação possui dois módulos principais: **cliente** (público) e **administrador** (admin).

---

## 📌 Descrição do Problema

Atualmente, várias empresas enfrentam dificuldades para organizar suas vendas e produtos em plataformas digitais. Processos manuais e desorganizados impactam diretamente na experiência do cliente e na gestão do negócio.

Este projeto tem como objetivo solucionar esse problema com um sistema web completo e funcional que:

- Exibe produtos de forma clara e atrativa.
- Permite que clientes adicionem itens ao carrinho e realizem pedidos.
- Garante controle de estoque.
- Disponibiliza um painel administrativo com cadastro e gerenciamento de produtos e categorias.

---

## 🚀 Funcionalidades

### 👤 Módulo Cliente

- Cadastro e login de usuários
- Visualização de produtos por categorias
- Filtro por preço, data e popularidade
- Carrinho de compras com:
  - Adição e remoção de itens
  - Validação automática da quantidade com base no estoque
  - Atualização do total em tempo real
- Finalização de pedido (checkout)
- Mensagens de feedback com notificações visuais

### 🛠️ Módulo Admin

- Login restrito para administradores
- Cadastro, edição e exclusão de produtos
- Cadastro de categorias
- Upload de imagens dos produtos
- Listagem dos produtos com ações rápidas (editar e excluir)
- Validação para exclusão dos carrinhos os produtos que já foram excluidos (restrição de integridade)

---

## 🧱 Estrutura Técnica

- **Frontend**:  
  HTML, CSS, Bootstrap 5, JavaScript

- **Backend**:  
  PHP (com PDO para acesso ao MySQL)

- **Banco de Dados**:  
  MySQL com as tabelas:
  - `User`
  - `Product`
  - `Category`
  - `Cart`
  - `CartItem`

---

## 📸 Diagrama lógico do database

> ![database](https://github.com/user-attachments/assets/5204e68c-2c27-4d1c-8bd6-bd0595039451)

---

## 🧪 Tecnologias Utilizadas

| Tecnologia | Função                          |
|------------|---------------------------------|
| PHP        | Lógica de backend               |
| MySQL      | Banco de dados relacional       |
| Bootstrap  | Estilização responsiva          |
| JavaScript | Interatividade e AJAX           |
| HTML/CSS   | Estrutura e layout              |
| Strapi     | API de pagamento                |

---

## Manual de execução:

- Garanta que o docker e docker-compose esteja instalado em sua maquina
  ```
      Execute o comando docker compose up
      Conteúdo estará disponível em http://localhost:8080
  ```

---
