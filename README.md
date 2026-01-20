# Joke — Микрофреймворк для PHP

[EN](#joke--a-micro-framework-for-php)

Joke — это учебный микрофреймворк с ручной маршрутизацией и встроенным DI-контейнером. Он разрабатывается в рамках
образовательного челленджа и не претендует на конкуренцию с промышленными решениями, такими как Laravel или Symfony.

Несмотря на скромный функционал, реализуемые компоненты стремятся быть надёжными, тестируемыми и пригодными для
использования в реальных проектах — в том числе в обучающих или прототипных задачах.

По условиям челенджа: не использовать существующие решения (кромe composer,
PHPUnit, [voral/version-increment](https://github.com/Voral/vs-version-incrementor))

# Требования

- PHP 8.4+
- Composer

# Реализованная функциональность

- Ручная маршрутизация HTTP-запросов
- DI-контейнер с поддержкой автовайринга параметров
- Система middleware (блокирующие и неблокирующие)
- Управление сессиями (включая поддержку «неблокирующего» режима — данные сессии считываются в начале обработки запроса,
  после чего сессия немедленно закрывается, позволяя другим запросам от того же пользователя работать параллельно без
  ожидания завершения текущего.)
- Обработка ошибок и исключений

# Как начать

1. Создайте новый проект (или используйте существующий):
    ```bash
    composer init
    ```
2. Установите ядро фреймворка:

    ```bash
    composer require voral/joke
    ```

3. Настройте точку входа.

   Убедитесь, что корневая директория веб-сервера указывает на папку public/. Например, при использовании встроенного
   сервера PHP:

    ```bash
    php -S localhost:8000 -t public/
    ```

Теперь ваше приложение доступно по адресу http://localhost:8000.

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

Joke is an educational microframework featuring manual routing and a built-in DI container. It is developed as part of a
learning challenge and is not intended to compete with production-grade frameworks like Laravel or Symfony.

Despite its limited scope, the implemented components aim to be reliable, testable, and suitable for real-world use—even
in educational or prototyping scenarios.

As per the challenge rules, no existing solutions may be used except for Composer, PHPUnit,
and [voral/version-increment](https://github.com/Voral/vs-version-incrementor).

## Requirements

- PHP 8.4+
- Composer

## Implemented Features

- Manual HTTP request routing
- DI container with automatic parameter wiring
- Middleware system (blocking and non-blocking)
- Session management, including **non-blocking mode** — session data is read at the beginning of request processing and
  the session is immediately closed, allowing concurrent requests from the same user to proceed without waiting for the
  current request to finish
- Error and exception handling

## Getting Started

1. Create a new project (or use an existing one):
   ```bash
   composer init
   ```

2. Install the framework core:
   ```bash
   composer require voral/joke
   ```

3. Configure the entry point.

   Make sure your web server’s document root points to the `public/` directory. For example, when using PHP’s built-in
   server:
   ```bash
   php -S localhost:8000 -t public/
   ```

Your application will now be available at [http://localhost:8000](http://localhost:8000).

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