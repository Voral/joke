# Минимальный скелетон

> Минимальный стартовый шаблон для проектов на основе

Этот скелетон предоставляет готовую структуру приложения с базовой маршрутизацией, контроллером и встроенным веб-сервером PHP — хорошо подходит для обучения или быстрого старта.

## Быстрый старт

Создайте новый проект одной командой:

```bash
composer create-project voral/joke-minimal my-app
cd my-app
```

Запустите встроенный сервер разработки:

```bash
composer run dev
```

Откройте в браузере: [http://localhost:8000](http://localhost:8000)

Вы увидите:
> **Hello from Joke Framework!**

## Структура проекта

```
my-app/
├── app/                # Код приложения (контроллеры, сервисы)
│   └── Controllers/
├── bootstrap/          # Инициализация фреймворка
│   └── app.php
├── public/             # Публичная точка входа
│   ├── index.php
│   └── .htaccess
├── routes/             # Определение маршрутов
│   └── web.php
└── composer.json
```

## Как добавить свой маршрут

1. **Создайте контроллер** в `app/Controllers/`, например:
   ```php
   // app/Controllers/AboutController.php
   namespace App\Controllers;
   
   class AboutController
   {
       public function __invoke(): string
       {
           return 'About page';
       }
   }
   ```

2. **Зарегистрируйте маршрут** в `routes/web.php`:
   ```php
   use App\Controllers\AboutController;
   
   $router->get('/about', AboutController::class);
   ```

3. Готово! Откройте [http://localhost:8000/about](http://localhost:8000/about)

## Поддержка ЧПУ

Скелетон включает `.htaccess` для Apache, который:
- Перенаправляет все запросы в `index.php`,
- Сохраняет заголовки `Authorization` и `X-XSRF-TOKEN`,
- Убирает завершающий слеш из URL.

Работает корректно на хостингах с включённым `mod_rewrite`.
