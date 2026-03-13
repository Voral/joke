# Реализованные middleware

Joke поставляется с набором встроенных middleware, обеспечивающих базовую функциональность веб-приложения: обработку
ошибок, управление сессией и защиту от CSRF-атак.

## ExceptionMiddleware

- Класс: Vasoft\Joke\Middleware\ExceptionMiddleware
- Уровень: глобальный
- Имя: StdMiddleware::EXCEPTION->value

Перехватывает необработанные исключения и преобразует их в корректные HTTP-ответы (например, 500 Internal Server Error)
и исключает ситуацию, когда пользователь увидит «голые» исключения PHP.

Регистрируется автоматически — дополнительная настройка не требуется.

## SessionMiddleware

- Класс: `Vasoft\Joke\Http\Middleware\SessionMiddleware`
- Уровень: маршрутизатора
- Имя: StdMiddleware::SESSION->value
- Режим: блокирующий (сессия остаётся открытой на всё время обработки запроса)

Запускает сессию, загружает данные и сохраняет их после выполнения цепочки middleware и обработчика. Это поведение по
умолчанию для большинства веб-приложений.

Используется автоматически для всех маршрутов из routes/web.php.

## ReadonlySessionMiddleware

- Класс: `Vasoft\Joke\Http\Middleware\ReadonlySessionMiddleware`
- Режим: неблокирующий (данные считываются, сессия немедленно закрывается)

Предназначен для сценариев, где:

- сессия нужна только для чтения (например, проверка авторизации),
- важна высокая параллельность (несколько AJAX-запросов от одного пользователя).

> Не сохраняет изменения в сессию. Если вам нужно записать данные — используйте SessionMiddleware.

Чтобы заменить стандартный middleware сессии для всех маршрутов:

```php
use Vasoft\Joke\Middleware\StdMiddleware;
use Vasoft\Joke\Http\Middleware\ReadonlySessionMiddleware;

// В bootstrap/app.php 
$app->addRouteMiddleware(ReadonlySessionMiddleware::class, StdMiddleware::SESSION->value);
```

Чтобы заменить стандартный middleware сессии для конкретного маршрута:

```php
use Vasoft\Joke\Middleware\StdMiddleware;
use Vasoft\Joke\Http\Middleware\ReadonlySessionMiddleware;

// В routes/web.php 
$router->get('/informer', Informer::class)
  ->addMiddleware(ReadonlySessionMiddleware::class, StdMiddleware::SESSION->value);
```

## CsrfMiddleware

- **Класс:** `Vasoft\Joke\Http\Csrf\CsrfMiddleware`
- **Уровень:** маршрутизатора
- **Имя:** `StdMiddleware::CSRF->value`
- **Применяется к:** группе `StdGroup::WEB->value` (т.е. всем маршрутам из `web.php`)

Обеспечивает защиту от межсайтовой подделки запроса (CSRF). Вся логика работы с токенами делегирована классу [
`CsrfTokenManager`](#csrftokenmanager).

### Как работает мидлвар

1. **Валидация запроса:** Вызывает `CsrfTokenManager::validate()` для проверки токена.
2. **Обработка запроса:** Передаёт управление следующему мидлвару или контроллеру.
3. **Внедрение токена:** Вызывает `CsrfTokenManager::attach()` для добавления токена в ответ.

### Проверка токена

Для небезопасных HTTP-методов (POST, PUT, DELETE, PATCH) мидлвар проверяет совпадение клиентского токена с серверным.
Токен ищется в порядке приоритета:

1. GET/POST параметр: `?csrf_token=...` или `csrf_token=...` (тело запроса)
2. HTTP-заголовок: `X-Csrf-Token: ...`
3. Cookie: `XSRF-TOKEN=...`

При несоответствии или отсутствии токена выбрасывает `CsrfMismatchException` (HTTP 403).

### Доставка токена клиенту

Способ доставки определяется конфигурацией [`CsrfConfig`](#csrfconfig):

| Режим                     | Куда добавляется                        | Рекомендуется для                        |
|:--------------------------|:----------------------------------------|:-----------------------------------------|
| **HEADER** (по умолчанию) | Заголовок `X-Csrf-Token`                | API, SPA-приложений                      |
| **COOKIE**                | Cookie `XSRF-TOKEN` (`httpOnly: false`) | Веб-приложений с паттерном Double Submit |

Токен генерируется автоматически при первом запросе. Вам нужно только передать его в форму или заголовок следующего
запроса.

---

### CsrfTokenManager

Сервис для управления жизненным циклом CSRF-токенов. Доступен через внедрение зависимости в контроллеры.

#### Публичные методы

| Метод                                         | Описание                                             | Когда использовать                     |
|:----------------------------------------------|:-----------------------------------------------------|:---------------------------------------|
| **`validate(HttpRequest $request): string`**  | Проверяет токен запроса, возвращает актуальный токен | В мидлваре (автоматически)             |
| **`reset(HttpRequest, Response): string`**    | Перегенерирует токен и добавляет в ответ             | Логин, смена пароля, эскалация прав    |
| **`invalidate(HttpRequest, Response): void`** | Семантический алиас `reset()` для логаута            | Логаут, блокировка пользователя        |
| **`attach(HttpRequest, Response): void`**     | Добавляет текущий токен из сессии в ответ            | Ручное внедрение в кастомных сценариях |

#### Пример использования в контроллере

```php
public function login(HttpRequest $request, Response $response, CsrfTokenManager $csrf): Response
{
    // ... аутентификация ...
    $csrf->reset($request, $response); // Перегенерировать токен для новой сессии
    return $response;
}

public function logout(HttpRequest $request, Response $response, CsrfTokenManager $csrf): Response
{
    // ... очистка сессии ...
    $csrf->invalidate($request, $response); // Отправить клиенту новый токен
    return $response;
}
```

---

### CsrfConfig

Конфигурация CSRF-защиты.

#### Настройки

| Параметр        | Тип                 | По умолчанию         | Описание                                |
|:----------------|:--------------------|:---------------------|:----------------------------------------|
| `transportMode` | `CsrfTransportMode` | `HEADER`             | Режим доставки токена (HEADER / COOKIE) |
| `cookieConfig`  | `CookieConfig`      | `new CookieConfig()` | Настройки кук (для режима COOKIE)       |

#### Пример настройки

```php
$csrfConfig = (new CsrfConfig())
    ->setTransportMode(CsrfTransportMode::COOKIE)
    ->setCookieConfig(
        (new CookieConfig())
            ->setLifetime(3600)
            ->setSecure(true)
            ->setSameSite('Strict')
    );
```

---

### Константы

| Класс              | Константа           | Значение         |
|:-------------------|:--------------------|:-----------------|
| `CsrfTokenManager` | `CSRF_TOKEN_NAME`   | `'csrf_token'`   |
| `CsrfTokenManager` | `CSRF_TOKEN_HEADER` | `'X-Csrf-Token'` |
| `CsrfTokenManager` | `CSRF_TOKEN_COOKIE` | `'XSRF-TOKEN'`   |

> **Важно:** Константы в `CsrfMiddleware` помечены как `@deprecated`. Используйте константы из `CsrfTokenManager`.

---

### Исключения

| Исключение              | Когда выбрасывается                                     | HTTP-статус |
|:------------------------|:--------------------------------------------------------|:------------|
| `CsrfMismatchException` | Токен клиента не совпадает с серверным                  | 403         |
| `CookieException`       | Ошибка добавления CSRF-куки в ответ                     | 500         |
| `RandomException`       | Не удалось сгенерировать криптографически стойкий токен | 500         |

