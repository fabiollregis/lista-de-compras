# Sistema de Lista de Compras

Um sistema web completo para gerenciamento de listas de compras, desenvolvido em PHP com MySQL.

## 📋 Funcionalidades

- Cadastro e login de usuários
- Criação e gerenciamento de listas de compras
- Adição, edição e remoção de itens
- Marcação de itens como comprados
- Histórico de compras
- Relatórios detalhados
- Perfil de usuário personalizável
- Produtos padrão pré-cadastrados

## 🔧 Requisitos

- PHP 7.0 ou superior
- MySQL 5.6 ou superior
- Servidor web (Apache/Nginx)
- PDO PHP Extension
- MySQL PHP Extension

## 🚀 Instalação

1. Clone o repositório ou faça o download dos arquivos
2. Coloque os arquivos na pasta do seu servidor web (ex: htdocs para XAMPP)
3. Configure o banco de dados em `config/database.php`:
   ```php
   $db_config = [
       'local' => [
           'host' => 'localhost',
           'dbname' => 'shopping_list',
           'username' => 'seu_usuario',
           'password' => 'sua_senha'
       ]
   ];
   ```
4. Acesse o sistema pelo navegador
5. O banco de dados e as tabelas serão criados automaticamente na primeira execução

## 📁 Estrutura do Projeto

```
lista-compras2/
├── api/                    # Endpoints da API
├── config/                 # Configurações do sistema
│   └── database.php       # Configuração do banco de dados
├── docs/                   # Documentação
├── index.php              # Página principal
├── login.php              # Sistema de login
├── register.php           # Cadastro de usuários
├── profile.php            # Perfil do usuário
├── reports.php            # Relatórios
└── [outros arquivos .php] # Arquivos de funcionalidades
```

## 🔒 Segurança

- Autenticação por sessão
- Prepared Statements para prevenção de SQL Injection
- Validação de entrada de dados
- Proteção contra CSRF
- Senhas criptografadas

## 💻 Uso

1. Faça login ou crie uma nova conta
2. Na página principal, adicione itens à sua lista
3. Para cada item, especifique:
   - Nome do produto
   - Quantidade
   - Preço estimado
4. Marque itens como comprados conforme necessário
5. Visualize relatórios e histórico de compras
6. Gerencie seu perfil e preferências

## 🛠️ Desenvolvimento

O sistema foi desenvolvido utilizando:
- PHP para backend
- MySQL para banco de dados
- HTML/CSS/JavaScript para frontend
- Bootstrap para interface responsiva
- PDO para conexão segura com banco de dados

## 📊 Banco de Dados

### Principais Tabelas:
- `users`: Dados dos usuários
- `shopping_items`: Itens da lista de compras
- `shopping_history`: Histórico de compras
- `shopping_history_items`: Itens do histórico

## 🤝 Contribuição

1. Faça um Fork do projeto
2. Crie uma Branch para sua Feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a Branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 📝 Licença

Este projeto está sob a licença MIT. Veja o arquivo `LICENSE` para mais detalhes.

## 📧 Contato

Para sugestões, dúvidas ou reportar problemas, por favor abra uma issue no repositório.

