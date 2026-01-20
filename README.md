# Joke — Микрофреймворк для PHP

[EN](#joke--a-micro-framework-for-php)


Joke — это учебный микрофреймворк с ручной маршрутизацией и встроенным DI-контейнером. Он разрабатывается в рамках
образовательного челленджа и не претендует на конкуренцию с промышленными решениями, такими как Laravel или Symfony.

Несмотря на скромный функционал, реализуемые компоненты стремятся быть надёжными, тестируемыми и пригодными для
использования в реальных проектах — в том числе в обучающих или прототипных задачах.

По условиям челенджа: не использовать существующие решения (кромe composer, PHPUnit, voral/version-increment)

# Требования

- PHP 8.4+
- Composer

# Реализованная функциональность

- Маршрутизация
- Сервисный контейнер

# Как начать

На данный момент релиз не опубликован, по этому для начала разработки необходимо клонировать репозиторий и установить
зависимости:

```bash
git clone https://github.com/Voral/joke.git
cd joke
composer install
```

Для запуска тестов:

```bash
composer test
```

Для запуска приложения либо настроить веб-сервер на каталог ./public, либо запустить локальный сервер:

```bash
compsoer dev
```

# Основные этапы челенджа

- [ ] **Создание фреймворка**
    - [x] Реализация маршрутизации
    - [x] Реализация сервисный контейнер
    - [ ] Консольная система команд
    - [ ] Авторизация
    - [ ] Валидация данных
    - [x] Обработка ошибок
    - [ ] Логирование
    - [ ] Создание построителя SQL запросов
    - [ ] Миграции
    - [ ] Настраиваемое окружение через .env файл
- [ ] **Создание шаблонизатора**
    - [ ] Создание базового шаблонизатора HTML
    - [ ] Рендер Markdown файлов для быстрого создания документационных сайтов из .md файлов.
    - [ ] Рендер Swagger yaml файлов для автоматического отображения OpenAPI-спецификаций без внешних UI (например, без
      Swagger UI)
- [ ] **Создание приложения форума**
- [ ] **Создание скелетонов**
    - [ ] REST API приложения
    - [ ] Приложения веб представления Markdown файлов

---- 

# Joke — A Micro Framework for PHP

This is a minimal micro-framework with manual routing and a dependency injection (DI) container, designed primarily for
educational purposes. It handles HTTP requests, invokes controllers via the container, and returns responses—but does *
*not** include an ORM, database migrations, or built-in authentication components.

As part of the challenge, no existing frameworks or component libraries are used—except for Composer, PHPUnit, and
`voral/version-increment`.

## Requirements

- PHP 8.4+
- Composer

## Implemented Features

- Routing
- Service Container

## Getting Started

No release has been published yet. To begin development, clone the repository and install dependencies:

```bash
git clone https://github.com/Voral/joke.git
cd joke
composer install
```

To run tests:

```bash
composer test
```

To run the application, either configure your web server to use the `./public` directory as the document root, or start
the built-in PHP development server:

```bash
composer dev
```

## Challenge Roadmap

- [ ] **Framework Core**
    - [x] Implement routing
    - [x] Implement service container
    - [ ] Console command system
    - [ ] Authentication
    - [ ] Data validation
    - [x] Error handling
    - [ ] Logging
    - [ ] SQL query builder
    - [ ] Database migrations
    - [ ] Environment configuration via `.env` file

- [ ] **Template Engine**
    - [ ] Basic HTML template engine
    - [ ] Render Markdown files for quickly generating documentation sites from `.md` files
    - [ ] Render Swagger YAML files to display OpenAPI specifications without external UIs (e.g., without Swagger UI)

- [ ] **Build a Forum Application**

- [ ] **Create Project Skeletons**
    - [ ] For REST API applications
    - [ ] For documentation sites based on Markdown files