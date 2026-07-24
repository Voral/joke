# Архитектура Rate Limiting и Storage Layer для Joke Framework

## Введение

Этот документ описывает архитектуру системы Rate Limiting, которая одновременно станет первым шагом к созданию универсального слоя хранения (Storage Layer) во фреймворке Joke.

## Требования

1. Интерфейс хранилища (Storage Interface) для хранения пар ключ-значение с TTL
2. Реализация "из коробки" (FileBasedStorage с учетом блокировок)
3. Алгоритм Rate Limiting с атомарностью операций
4. Интеграция через Service Provider с гибкими настройками
5. Механизм идентификации клиентов с возможностью расширения

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
│  └─ SlidingWindowRateLimiter (Token Bucket alternative)             │
└─────────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│                        Middleware                                   │
├─────────────────────────────────────────────────────────────────────┤
│  RateLimitMiddleware                                                │
└─────────────────────────────────────────────────────────────────────┘
```

## Реализация

### 1. StorageInterface (storage-interface.php)

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

### 2. FileBasedStorage (file-based-storage.php)

Особенности:
- Структура: `storage/ratelimit/data/{prefix}/{hash}.json`
- Файловые блокировки (flock) для многопроцессной безопасности
- Атомарная запись через tempnam + rename
- Формат: `{"value": "...", "expires_at": timestamp}`

### 3. Алгоритм Rate Limiting

**Выбор: Sliding Window Log**

Почему Sliding Window Log вместо Token Bucket:
- Проще реализовать на файловом хранилище без сложных транзакций
- Точное ограничение по времени (не "окно" как в Token Bucket)
- Поддержка burst-поведения через хранение временных меток

Алгоритм:
1. Хранить массив временных меток (timestamps) для каждого клиента
2. При каждом запросе удалять устаревшие метки (старше окна)
3. Проверять количество оставшихся меток
4. Если лимит не превышен - добавить новую метку

Атомарность достигается через CAS-операцию.

### 4. Service Provider

```php
class RateLimiterServiceProvider extends AbstractProvider
{
    public function register(): void
    {
        // Регистрация StorageInterface -> FileBasedStorage
        // Регистрация RateLimiterInterface -> SlidingWindowRateLimiter
    }
    
    public function provides(): array
    {
        return [StorageInterface::class, RateLimiterInterface::class];
    }
}
```

### 5. Конфигурация

```php
// config/ratelimit.php
return new RateLimitConfig()
    ->setDefaultLimit(100)      // запросов
    ->setDefaultWindow(60)      // секунд
    ->setDriver('file')         // file|array|redis
    ->setStoragePath(storage_path('ratelimit'));
```

### 6. Идентификация клиентов

```php
interface ClientIdentifierInterface
{
    public function identify(HttpRequest $request): string;
}

class DefaultClientIdentifier implements ClientIdentifierInterface
{
    public function identify(HttpRequest $request): string
    {
        return $request->server->getString('REMOTE_ADDR', 'unknown');
    }
}
```

## Схема взаимодействия

```
HttpRequest
    │
    ▼
RateLimitMiddleware::handle()
    │
    ▼
RateLimiter::check(string $clientKey, int $limit, int $window)
    │
    ▼
StorageInterface::cas($key, $callback, $ttl)
    │
    ▼
FileBasedStorage::cas() with flock()
    │
    ▼
JSON: {"value": "[timestamp1,timestamp2,...]", "version": 1}
    │
    ▼
Middleware проверяет результат
    │
    ├─ OK → вызов $next($request)
    └─ LIMIT EXCEEDED → Response 429
```

## Файловая структура

```
src/
├── Contract/
│   └── Storage/
│       ├── StorageInterface.php
│       └── StorageException.php
├── Storage/
│   ├── FileBasedStorage.php
│   ├── ArrayStorage.php
│   └── Exceptions/
│       └── StorageException.php
├── RateLimiter/
│   ├── RateLimiterInterface.php
│   ├── SlidingWindowRateLimiter.php
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

## Аргументация выбора технологий

### Почему FileBasedStorage для старта?

1. **Нулевая зависимость** - не требует внешних сервисов (Redis, Memcached)
2. **Простота разработки** - легко отлаживать, читать сохраненные данные
3. **Безопасность** - flock() обеспечивает конкурентный доступ
4. **Типизация** - строгая типизация JSON с version для CAS
5. **Гибкость** - легко добавить драйверы в будущем

### Почему Sliding Window Log для Rate Limiting?

1. **Простота на файловом хранилище** - хранение массива timestamps
2. **Точность** - реальное ограничение по времени, не "окно"
3. **Поддержка burst** - можно пропускать первые N запросов
4. **Мало операций I/O** - одна CAS-операция на запрос

## Дополнительные возможности

### Расширяемая идентификация

```php
// В config/ratelimit.php
->setClientIdentifier(CustomClientIdentifier::class)
```

### Гибкие лимиты

```php
// В контроллере или роуте
->middleware(
    RateLimitMiddleware::class . ':10,60'  // 10 запросов в 60 секунд
)
```

### Метрики

```php
// Для мониторинга
$rateLimiter->getStats(string $clientKey): array
```

## Заключение

Эта архитектура:
- ✓ Создает основу для Storage Layer
- ✓ Работает "из коробки" без внешних зависимостей
- ✓ Поддерживает конкурентный доступ
- ✓ Гибкая и расширяемая
- ✓ Простая для понимания и отладки
