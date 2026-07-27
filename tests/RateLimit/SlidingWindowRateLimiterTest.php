<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\RateLimit;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Contract\RateLimit\ClientIdentifierInterface;
use Vasoft\Joke\Http\HttpRequest;
use Vasoft\Joke\RateLimit\SlidingWindowRateLimiter;
use Vasoft\Joke\Storage\ArrayStorage;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\RateLimit\SlidingWindowRateLimiter
 */
final class SlidingWindowRateLimiterTest extends TestCase
{
    private ArrayStorage $storage;
    private MockClientIdentifier $identifier;
    private SlidingWindowRateLimiter $limiter;
    private HttpRequest $request;

    protected function setUp(): void
    {
        $this->storage = new ArrayStorage();
        $this->identifier = new MockClientIdentifier();
        $this->limiter = new SlidingWindowRateLimiter(
            $this->storage,
            $this->identifier,
            limit: 5,
            window: 3600,
        );
        $this->request = new HttpRequest();
    }

    /**
     * Проверяет, что счётчик инкрементируется атомарно.
     *
     * Каждый запрос должен увеличивать счётчик на 1,
     * и возвращаемый remaining должен соответствовать лимиту - счётчику.
     */
    public function testAtomicIncrement(): void
    {
        $this->identifier->setClientId('client1');

        // Первый запрос: счётчик = 1, remaining = 4
        $info = $this->limiter->check($this->request);
        self::assertFalse($info->isBlocked);
        self::assertSame(4, $info->remaining);
        self::assertSame(5, $info->limit);

        // Второй запрос: счётчик = 2, remaining = 3
        $info = $this->limiter->check($this->request);
        self::assertFalse($info->isBlocked);
        self::assertSame(3, $info->remaining);

        // Третий запрос: счётчик = 3, remaining = 2
        $info = $this->limiter->check($this->request);
        self::assertFalse($info->isBlocked);
        self::assertSame(2, $info->remaining);

        // Четвёртый запрос: счётчик = 4, remaining = 1
        $info = $this->limiter->check($this->request);
        self::assertFalse($info->isBlocked);
        self::assertSame(1, $info->remaining);

        // Пятый запрос: счётчик = 5, remaining = 0
        $info = $this->limiter->check($this->request);
        self::assertFalse($info->isBlocked);
        self::assertSame(0, $info->remaining);

        // Шестой запрос: счётчик = 6, превышен лимит!
        $info = $this->limiter->check($this->request);
        self::assertTrue($info->isBlocked);
        self::assertSame(0, $info->remaining);  // Не показываем отрицательные значения
    }

    /**
     * Проверяет, что превышающий запрос также инкрементирует счётчик.
     *
     * Вариант A: превышающий запрос наказывает бота через счётчик.
     */
    public function testExceedingRequestIncrementsCounter(): void
    {
        $this->identifier->setClientId('client2');

        // Делаем 5 успешных запросов
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->check($this->request);
        }

        // Проверяем, что 6-й запрос превышен, но счётчик растёт
        $info1 = $this->limiter->check($this->request);
        self::assertTrue($info1->isBlocked);

        // 7-й запрос — счётчик снова вырос
        $info2 = $this->limiter->check($this->request);
        self::assertTrue($info2->isBlocked);

        // Убеждаемся, что счётчик действительно растёт за каждый запрос
        $rawCounter = $this->storage->get('ratelimit:client2');
        self::assertSame(7, $rawCounter);
    }

    /**
     * Проверяет истечение TTL.
     *
     * Sliding window сбрасывается через window'а от ПОСЛЕДНЕГО запроса.
     * Если между запросами прошло больше window'а, счётчик обнулится.
     */
    public function testTtlExpiry(): void
    {
        $this->identifier->setClientId('client3');
        $this->storage->setCurrentTime(1000);

        // Делаем 3 запроса в момент 1000
        // expiresAt = 1000 + 3600 = 4600
        for ($i = 0; $i < 3; $i++) {
            $this->limiter->check($this->request);
        }
        self::assertSame(3, $this->storage->get('ratelimit:client3'));

        // На момент 4599 (до истечения) счётчик жив, но НЕ обновляем его
        // Используем get() вместо check()
        $this->storage->setCurrentTime(4599);
        $counter = $this->storage->get('ratelimit:client3');
        self::assertSame(3, $counter);

        // На момент 4600 (окно ИСТЕКЛО), счётчик удалится
        // Первый запрос был на 1000, expiresAt = 4600
        $this->storage->setCurrentTime(4600);
        $counter = $this->storage->get('ratelimit:client3');
        self::assertSame(0, $counter);  // Старые данные удалены

        // Новый запрос в этот момент создаёт новый счётчик
        $info = $this->limiter->check($this->request);
        self::assertSame(1, $this->storage->get('ratelimit:client3'));
        self::assertFalse($info->isBlocked);
        self::assertSame(4, $info->remaining);
    }

    /**
     * Проверяет граничное поведение скользящего окна.
     *
     * Счётчик сбрасывается через window'а от ПОСЛЕДНЕГО запроса.
     */
    public function testSlidingWindowBoundary(): void
    {
        $this->identifier->setClientId('client4');
        $this->storage->setCurrentTime(1000);

        // Момент 1000: делаем 3 запроса
        // expiresAt = 1000 + 3600 = 4600
        for ($i = 0; $i < 3; $i++) {
            $this->limiter->check($this->request);
        }
        self::assertSame(3, $this->storage->get('ratelimit:client4'));

        // Момент 1800: делаем 1 запрос (без обновления)
        // Просто проверяем, что счётчик жив
        $this->storage->setCurrentTime(1800);
        $counter = $this->storage->get('ratelimit:client4');
        self::assertSame(3, $counter);

        // Момент 4600: окно первого набора истекло
        // Счётчик удалится, так как expiresAt = 4600
        $this->storage->setCurrentTime(4600);
        $counter = $this->storage->get('ratelimit:client4');
        self::assertSame(0, $counter);

        // Новый запрос в момент 4600: счётчик создаётся заново
        $info = $this->limiter->check($this->request);
        self::assertSame(1, $this->storage->get('ratelimit:client4'));
        self::assertFalse($info->isBlocked);

        // Момент 8200: окно НОВОГО запроса истекло (4600 + 3600 = 8200)
        $this->storage->setCurrentTime(8200);
        $counter = $this->storage->get('ratelimit:client4');
        self::assertSame(0, $counter);
    }

    /**
     * Проверяет, что разные клиенты имеют независимые лимиты.
     */
    public function testIndependentPerClient(): void
    {
        // Клиент А делает 3 запроса
        $this->identifier->setClientId('clientA');
        for ($i = 0; $i < 3; $i++) {
            $this->limiter->check($this->request);
        }

        // Клиент B делает 1 запрос
        $this->identifier->setClientId('clientB');
        $infoB = $this->limiter->check($this->request);

        // Проверяем, что лимиты независимы
        self::assertSame(3, $this->storage->get('ratelimit:clientA'));
        self::assertSame(1, $this->storage->get('ratelimit:clientB'));
        self::assertFalse($infoB->isBlocked);
        self::assertSame(4, $infoB->remaining);
    }
}

/**
 * Mock для ClientIdentifierInterface, позволяет явно устанавливать ID клиента.
 */
final class MockClientIdentifier implements ClientIdentifierInterface
{
    private string $clientId = 'default';

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function identify(HttpRequest $request): string
    {
        return $this->clientId;
    }
}
