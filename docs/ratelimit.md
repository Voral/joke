# Rate Limiting и Storage Layer

## Введение

В фреймворке Joke реализована система Rate Limiting, которая одновременно стала первым шагом к созданию универсального слоя хранения (Storage Layer).

## Архитектура

```
┌─────────────────────────────────────────────────────────────────────┐
│                        Storage Layer                                │
├─────────────────────────────────────────────────────────────────────┤
│  StorageInterface (Contract)                                        │
│  ├─ FileBasedStorage (FileDriver)                                   │
│  ├─ ArrayStorage (For Testing)                                      │
│  └─ RedisStorage (Future)                                           │
└─────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│                        Rate Limiter                                 │
├─────────────────────────────────────────────────────────────────────┤
│  RateLimiterInterface                                               │
│  └─ SlidingWindowRateLimiter                                        │
└─────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│                        Middleware                                   │
├─────────────────────────────────────────────────────────────────────┤
│  RateLimitMiddleware                                                │
└─────────────────────────────────────────────────────────────────────┘
```

## Использование

### 1. Регистрация через Service Provider

```php
use Vasoft\Joke\Provider\RateLimiterServiceProvider;

// В bootstrap/app.php
$app = new Application(dirname(__DIR__), 'routes/web.php', new ServiceContainer());

// Регистрируем провайдер
$app->register(new RateLimiterServiceProvider());
```

### 2. Использование в роутах

```php
// В routes/web.php
$router->get('/api/users', 'UserController@index')
    ->middleware(RateLimitMiddleware::class); // 100 запросов в минуту по умолчанию

// С кастомными лимитами
$router->post('/api/login', 'AuthController@login')
    ->middleware(RateLimitMiddleware::class . ':5,300'); // 5 запросов в 5 минут
```

### 3. Конфигурация

Создайте файл `config/ratelimit.php`:

```php
<?php

declare(strict_types=1);

use Vasoft\Joke\Config\RateLimitConfig;

return (new RateLimitConfig())
    ->setDefaultLimit(100)           // 100 запросов в минуту
    ->setDefaultWindow(60)           // 60 секунд
    ->setDriver('file')              // file|array|redis
    ->setStoragePath(storage_path('ratelimit'))
    ->setClientIdentifierClass(\Vasoft\Joke\RateLimiter\DefaultClientIdentifier::class)
    ->setRouteLimit('api.login', 5, 300)  // Кастомный лимит для конкретного маршрута
    ->freeze();
```

### 4. Гибкие лимиты

```php
// В контроллере
$rateLimiter = $container->make(RateLimiterInterface::class);
$clientKey = $request->server->getString('REMOTE_ADDR');

$result = $rateLimiter->check($clientKey, 100, 60);

if (!$result['allowed']) {
    return new JsonResponse([
        'error' => 'Too Many Requests',
        'retry_after' => $result['retryAfter'],
    ], 429);
}
```

## Концепции

### Storage Interface

Универсальный интерфейс для хранения данных:

```php
interface StorageInterface
{
    public function get(string $key): ?string;
    public function set(string $key, string $value, int $ttl = 0): bool;
    public function delete(string $key): bool;
    public function cas(
        string $key,
        callable $callback,
        int $ttl = 0,
        int $maxRetries = 10
    ): array;
    public function clear(): bool;
}
```

### CAS (Compare-and-Swap)

Атомарная операция для безопасного обновления данных в многопроцессной среде:

```php
$result = $storage->cas('counter', function (?string $value) {
    $current = $value !== null ? (int)$value : 0;
    return (string)($current + 1);
});
```

### Алгоритм Sliding Window Log

Rate Limiter использует алгоритм Sliding Window Log:

1. Хранит массив временных меток (timestamps) для каждого клиента
2. При каждом запросе удаляет устаревшие метки (старше окна)
3. Проверяет количество оставшихся меток
4. Если лимит не превышен - добавляет новую метку

**Преимущества:**
- Простота реализации на файловом хранилище
- Точное ограничение по времени
- Поддержка burst-поведения

### Идентификация клиентов

По умолчанию используется IP-адрес клиента:

```php
class DefaultClientIdentifier implements ClientIdentifierInterface
{
    public function identify(HttpRequest $request): string
    {
        // Приоритет: X-Forwarded-For → X-Real-IP → REMOTE_ADDR
        return $request->server->getString('REMOTE_ADDR', 'unknown');
    }
}
```

Можно создать кастомный идентификатор:

```php
class ApiKeyClientIdentifier implements ClientIdentifierInterface
{
    public function identify(HttpRequest $request): string
    {
        return $request->headers->getString('X-API-Key', 'anonymous');
    }
}
```

## Файловая структура

```
src/
├── Contract/
│   ├── Storage/
│   │   ├── StorageInterface.php
│   │   ├── StorageException.php
│   │   └── RateLimiterInterface.php
│   └── Storage/
├── Storage/
│   ├── FileBasedStorage.php
│   ├── ArrayStorage.php
│   └── StorageException.php
├── RateLimiter/
│   ├── SlidingWindowRateLimiter.php
│   ├── RateLimitMiddleware.php
│   ├── ClientIdentifierInterface.php
│   └── DefaultClientIdentifier.php
└── Provider/
    └── RateLimiterServiceProvider.php

config/
└── ratelimit.php

storage/
└── ratelimit/
    └── data/
        └── {prefix}/{hash}.json
```

## Заголовки ответа

Rate Limiting добавляет следующие заголовки в ответ:

```
X-RateLimit-Limit: 100          # Максимальное количество запросов
X-RateLimit-Remaining: 99       # Оставшееся количество запросов
X-RateLimit-Reset: 1783665000   # Время сброса лимита (timestamp)
```

При превышении лимита:

```
HTTP/1.1 429 Too Many Requests
Retry-After: 45
Content-Type: application/json

{
    "error": "Too Many Requests",
    "message": "Rate limit exceeded. Please try again later.",
    "retry_after": 45
}
```

## Тестирование

### Unit-тесты

```bash
php vendor/bin/phpunit tests/Storage/
php vendor/bin/phpunit tests/RateLimiter/
php vendor/bin/phpunit tests/Provider/
```

### Интеграционное тестирование

```php
// tests/Feature/RateLimitingTest.php
public function testRateLimiting(): void
{
    $clientKey = '192.168.1.1';
    $limit = 3;
    $window = 60;

    for ($i = 0; $i < $limit; $i++) {
        $response = $this->get('/api/test');
        $this->assertEquals(200, $response->status);
    }

    // 4-й запрос должен быть отклонен
    $response = $this->get('/api/test');
    $this->assertEquals(429, $response->status);
}
```

## Расширение

### Добавление Redis-драйвера

```php
class RedisStorage implements StorageInterface
{
    private \Redis $redis;

    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }

    // Реализация методов StorageInterface
}
```

### Кастомный идентификатор клиентов

```php
class UserBasedClientIdentifier implements ClientIdentifierInterface
{
    public function identify(HttpRequest $request): string
    {
        // Если пользователь аутентифицирован - используем его ID
        if ($request->session->has('user_id')) {
            return 'user_' . $request->session->get('user_id');
        }
        
        // Иначе используем IP
        return 'ip_' . $request->server->getString('REMOTE_ADDR');
    }
}
```

## Заключение

Эта архитектура:
- ✓ Создает основу для Storage Layer
- ✓ Работает "из коробки" без внешних зависимостей
- ✓ Поддерживает конкурентный доступ через flock()
- ✓ Гибкая и расширяемая
- ✓ Простая для понимания и отладки
