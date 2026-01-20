# DI-контейнер

Joke включает встроенный контейнер внедрения зависимостей (DI Container), который автоматически разрешает зависимости при создании объектов, вызове middleware и обработке маршрутов. Контейнер интегрирован с системой маршрутизации и поддерживает гибкую настройку через регистрацию сервисов.

Реализован в классе `Vasoft\Joke\Core\ServiceContainer`.

## Основные возможности

- **Автоматическое внедрение** через конструктор и callable,
- **Поддержка синглтонов** и прототипов,
- **Интеграция с контекстом запроса** (например, параметры маршрута),
- **Автоматическая десериализация** через `tryFrom()` (включая enum),
- **Ленивая инициализация** с кэшированием экземпляров.

## Регистрация сервисов

### Синглтоны (один экземпляр на всё приложение)

```php
$container->registerSingleton(LoggerInterface::class, FileLogger::class);
```

Поддерживаются:
- **Строки с именем класса** → создаётся один раз,
- **Callable (фабрики)** → вызывается один раз,
- **Готовые объекты** → используются напрямую.

### Прототипы (новый экземпляр при каждом вызове)

```php
$container->register(EmailSender::class, SmtpEmailSender::class);
```

Каждый вызов `$container->get(EmailSender::class)` создаёт новый объект.

> Если вы передаёте **готовый объект** в `register()`, он автоматически сохраняется как синглтон — это предотвращает случайную утечку состояния.

## Получение сервисов

```php
$logger = $container->get(LoggerInterface::class);
```
- Возвращает `null`, если сервис не зарегистрирован.
- Не выбрасывает исключение — это позволяет реализовывать опциональные зависимости.

## Как работает автовайринг

Когда контейнеру нужно создать объект или вызвать callable, он использует **`ParameterResolver`** (`Vasoft\Joke\Core\Routing\ParameterResolver`) для анализа параметров. Процесс состоит из двух этапов:

### 1. Поиск в контексте
Если параметр совпадает по имени с переменной из контекста (например, `{id}` из маршрута `/user/{id}`), он используется **в первую очередь**.

- Если тип параметра — **скалярный** (int, string и т.д.) → значение берётся как есть.
- Если тип — **класс с методом `tryFrom()`** (например, enum) → вызывается `MyEnum::tryFrom($value)`.
- Если тип — **класс без `tryFrom()`** → выбрасывается `AutowiredException`.

### 2. Поиск в DI-контейнере
Если параметр не найден в контексте, но имеет типизацию объекта — контейнер пытается разрешить его как сервис:

```php
function handle(UserRepository $users) { ... }
// → $users = $container->get(UserRepository::class)
```

Если сервис не зарегистрирован — выбрасывается `AutowiredException`.

> ❗ Порядок важен: **контекст > DI-контейнер**. Это позволяет переопределять зависимости на уровне маршрута.

## Примеры использования

### Внедрение в обработчик маршрута

```php
$router->get('/user/{id:int}', function (int $id, UserRepository $users) {
    return $users->find($id);
});
```

- `$id` берётся из URI и преобразуется в `int`,
- `$users` — из DI-контейнера.

### Использование enum с `tryFrom`

```php
enum OrderStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
}

$router->get('/order/{status}', function (OrderStatus $status) {
    // ...
});
```

Если в URI будет `/order/invalid`, `tryFrom` вернёт `null`, и фреймворк выбросит `AutowiredException`.

### Фабрика с доступом к контейнеру

```php
$container->registerSingleton(Cache::class, function (ServiceContainer $c) {
    $config = $c->get(Config::class);
    return new RedisCache($config->host);
});
```

Фабрика сама получает контейнер через автовайринг.

---

## Обработка ошибок

Если параметр не может быть разрешён, выбрасывается:

```php
Vasoft\Joke\Core\Routing\Exceptions\AutowiredException
```

Сообщение включает имя параметра и ожидаемый тип, например:

> `Failed to autowire parameter "$status": expected type "OrderStatus" cannot be resolved or is incompatible with the provided value.`

Это помогает быстро находить причины ошибок при разработке.

---

## Встроенные сервисы

При создании контейнер автоматически регистрирует:

| Интерфейс | Реализация |
|---------|-----------|
| `ResolverInterface` | `ParameterResolver` |
| `RouterInterface` | `Router` |

Вы можете заменить их, перерегистрировав интерфейс:

```php
$container->registerSingleton(RouterInterface::class, MyRouter::class);
```

---

## Особенности и ограничения

- **Циклические зависимости** не поддерживаются.
- **Примитивные типы без контекста** (например, `function(int $x)`) не могут быть разрешены — всегда требуют значения из контекста.
- **Параметры по умолчанию** пока не поддерживаются (см. `@todo` в коде).
- **Рефлексия не кэшируется** — в будущем планируется оптимизация.

---

## Практический пример

```php
// bootstrap/app.php
$container = new ServiceContainer();
$container->registerSingleton(LoggerInterface::class, FileLogger::class);
$container->registerSingleton(UserRepository::class, DbUserRepository::class);

return new Application(__DIR__ . '/..', 'routes/web.php', $container);
```

Теперь в любом обработчике:

```php
$router->get('/profile', function (UserRepository $users, LoggerInterface $log) {
    $log->info('Profile accessed');
    return $users->current();
});
```

Всё работает «из коробки» — без ручной передачи зависимостей.
