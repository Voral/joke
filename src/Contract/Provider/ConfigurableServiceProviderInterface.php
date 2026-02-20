<?php

declare(strict_types=1);

namespace Vasoft\Joke\Contract\Provider;

use Vasoft\Joke\Config\AbstractConfig;
use Vasoft\Joke\Config\Exceptions\UnknownConfigException;
use Vasoft\Joke\Container\ServiceContainer;

/**
 * Интерфейс для провайдеров, поддерживающих трехуровневую систему конфигурации:
 * 1. Файлы (Eager/Lazy)
 * 2. Дефолты от провайдера (Fallback)
 */
interface ConfigurableServiceProviderInterface extends ServiceProviderInterface
{
    /**
     * Возвращает список классов конфигураций, которые этот провайдер умеет создавать по умолчанию.
     * Используется на этапе сканирования для регистрации "резервных" путей в контейнере.
     *
     * @return list<class-string<AbstractConfig>> Список полных имен классов конфигов
     */
    public static function provideConfigs(): array;

    /**
     * Создает экземпляр конфигурации по умолчанию для заданного класса.
     * Вызывается контейнером только если конфиг не найден ни в основных файлах, ни в lazy-каталоге.
     *
     * @template T of AbstractConfig
     *
     * @param class-string<T>  $configClass Класс конфигурации
     * @param ServiceContainer $container   Контейнер зависимостей
     *
     * @return T Объект заданного класса
     *
     * @throws UnknownConfigException Если провайдер не знает, как создать данный класс
     */
    public static function buildConfig(string $configClass, ServiceContainer $container): AbstractConfig;
}
