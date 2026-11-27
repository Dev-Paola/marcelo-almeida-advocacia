# Landing Page — Marcelo Almeida Advocacia

Landing page profissional para um escritório de advocacia, desenvolvida em HTML, CSS e JavaScript puros, com API em PHP 8.3 e persistência SQLite.

O projeto apresenta o advogado, suas áreas de atuação, diferenciais, processo de atendimento, depoimentos, informações de contato e um formulário de solicitação de consulta integrado à API.

## Visão geral

- Página inicial com chamada para consulta e contatos diretos
- Menu responsivo para dispositivos móveis
- Áreas de atuação geradas dinamicamente
- Seção institucional sobre o advogado
- Processo de atendimento em três etapas
- Depoimentos de clientes
- Banner com acesso direto ao WhatsApp
- Formulário de triagem jurídica enviado via `fetch` com JSON
- Validação no servidor, honeypot e limite de envios por IP
- Persistência de consultas em SQLite via PDO
- Rodapé com contato, navegação e registro profissional
- Layout adaptado para desktop, tablet e celular
- Suporte a navegação por teclado e atributos de acessibilidade

## Tecnologias

- HTML5
- CSS3
- JavaScript (Fetch API)
- PHP 8.3 (PDO)
- SQLite
- Google Fonts
- Unsplash para imagens ilustrativas

Não há frameworks externos, dependências JavaScript ou etapa de compilação. O PHP precisa ter as extensões PDO e PDO SQLite habilitadas.

## Estrutura do projeto

```text
.
├── index.html     # Estrutura e conteúdo da página
├── styles.css     # Layout, componentes e responsividade
├── script.js      # Menu, dados das seções e geração de elementos
├── api/
│   └── agendar.php # Endpoint de validação e gravação de consultas
├── database/
│   ├── .htaccess   # Bloqueia acesso direto ao banco no Apache
│   └── .gitkeep    # Mantém a pasta de dados no Git
├── .gitignore      # Ignora o banco SQLite local
└── README.md      # Documentação do projeto
```

## Como executar

O formulário depende do backend PHP e não funciona ao abrir `index.html` diretamente ou servir o projeto com um servidor apenas estático.

Na raiz do projeto, inicie o servidor PHP embutido:

```bash
php -S 127.0.0.1:8000
```

Abra a página em:

```text
http://127.0.0.1:8000
```

O diretório `database/` precisa ter permissão de escrita para o usuário que executa o PHP. O endpoint cria automaticamente `database/consultas.sqlite` e as tabelas necessárias.

O arquivo `.htaccess` bloqueia o acesso HTTP direto à pasta em servidores Apache. Em Nginx ou outro servidor, configure uma regra equivalente para negar acesso a `database/`; não publique o banco SQLite como arquivo estático.

## Testes automatizados da API

Execute na raiz do projeto:

```bash
php tests/agendar_test.php
```

A suíte usa `PDO` com SQLite em memória, sem criar arquivos de banco. Ela verifica a persistência e sanitização de um agendamento válido (incluindo o hash do IP), erros de validação sem gravação, bloqueio pelo honeypot e limite de três envios por IP.

## API de consultas

`POST /api/agendar.php` recebe `Content-Type: application/json` com os campos:

```json
{
  "nome": "Maria da Silva",
  "email": "maria@example.com",
  "telefone": "(11) 99999-9999",
  "area_atuacao": "Direito Civil",
  "mensagem": "Preciso de orientação sobre um contrato."
}
```

O endpoint responde com `201` quando grava a solicitação, `422` com erros por campo quando os dados são inválidos e `429` quando o IP excede três envios em dez minutos. O campo honeypot `website` (ou `company`) é ignorado e não gera gravação.

Cada consulta armazena um hash SHA-256 do IP, não o endereço IP em texto. O banco contém informações pessoais e relatos potencialmente sensíveis: mantenha-o fora do controle de versão, limite o acesso no servidor e defina procedimentos de retenção e exclusão adequados.

## Personalização

Grande parte das informações pode ser ajustada diretamente nos arquivos:

- **Conteúdo principal e estrutura:** `index.html`
- **Cores, tipografia, espaçamento e responsividade:** `styles.css`
- **Áreas de atuação, formulário, diferenciais, etapas, depoimentos e dados do rodapé:** `script.js`
- **Áreas aceitas pela API:** lista `$areasPermitidas` em `api/agendar.php` (mantenha-a sincronizada com `practiceAreas` em `script.js`)

Os telefones, e-mails e endereço atualmente são dados de exemplo. Antes da publicação, substitua todas as ocorrências:

```text
(11) 99999-9999
+5511999999999
contato@marceloalmeida.adv.br
Av. Paulista, 1000 — São Paulo, SP
OAB/SP Nº 000.000
```

Para atualizar o WhatsApp, altere o número no link `https://wa.me/5511999999999` e nos demais contatos de telefone.

## Imagens

As imagens do advogado são carregadas de uma URL externa do Unsplash e possuem comentários no `index.html` indicando os pontos de substituição.

Para utilizar retratos oficiais:

1. Adicione as imagens na pasta do projeto.
2. Substitua os valores de `src` das classes `.portrait-image` e `.about-image`.
3. Atualize o atributo `alt` com uma descrição adequada.

Exemplo:

```html
<img
  class="portrait-image"
  src="assets/marcelo-almeida.jpg"
  alt="Retrato profissional do advogado Marcelo Almeida"
>
```

## Responsividade

Os estilos possuem breakpoints para:

- Desktop
- Até 1120 px
- Até 860 px
- Até 768 px
- Até 380 px

Para testar, utilize as ferramentas de desenvolvedor do navegador e selecione diferentes tamanhos de tela.

## Publicação

O projeto requer uma hospedagem que execute PHP 8.3 e disponibilize PDO SQLite; uma hospedagem estática não executa o endpoint. Configure o servidor para encaminhar as requisições PHP, permita que o processo PHP escreva em `database/` e bloqueie acesso HTTP direto a essa pasta (a regra `.htaccess` cobre Apache).

Antes da publicação, revise e substitua os dados fictícios, textos provisórios, imagens ilustrativas e links de contato.

Use uma hospedagem PHP ou servidor próprio que atenda a esses requisitos. Serviços de hospedagem estática não executam `api/agendar.php`; neles, o formulário só funcionará se a API for publicada separadamente em um ambiente compatível.
