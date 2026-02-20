<?php

declare(strict_types=1);

namespace Vasoft\Joke\Application;

use Vasoft\Joke\Config\AbstractConfig;
use Vasoft\Joke\Contract\Provider\ServiceProviderInterface;
use Vasoft\Joke\Config\Exceptions\ConfigException;
use Vasoft\Joke\Routing\RouterServiceProvider;

/**
 * Конфигурация ядра приложения.
 *
 * Отвечает за регистрацию сервис-провайдеров, определяющих структуру приложения.
 * Поддерживает разделение на обычные провайдеры (загружаются сразу) и отложенные (ленивые).
 *
 * Используется в файле `bootstrap/kernel.php` для декларативной настройки приложения.
 * Если файл отсутствует, используются провайдеры по умолчанию.
 *
 * @see ServiceProviderInterface
 */
class KernelConfig extends AbstractConfig
{
    /**
     * Список классов обычных сервис-провайдеров.
     * Провайдеры из этого списка инициируются сразу при старте приложения.
     * Включает провайдеры ядра и маршрутизации по умолчанию.
     *
     * @var list<class-string<ServiceProviderInterface>>
     */
    private array $providers = [
        KernelServiceProvider::class,
        RouterServiceProvider::class,
    ];

    /**
     *  Список классов отложенных (ленивых) сервис-провайдеров.
     *  Провайдеры из этого списка могут быть инициированы только при обращении к предоставляемым ими сервисам.
     *
     * @var list<class-string<ServiceProviderInterface>>
     */
    private array $deferredProviders = [];
    /**
     * Путь к директории с базовыми конфигурациями.
     */
    private string $baseConfigPath = 'config';
    /**
     * Путь к директории с ленивыми конфигурациями.
     */
    private string $lazyConfigPath = 'config/lazy';

    /**
     * Добавляет класс провайдера в список обычных провайдеров.
     *
     * @param class-string<ServiceProviderInterface> $class Класс провайдера
     *
     * @return $this
     *
     * @throws ConfigException Если конфигурация заблокирована для изменений
     */
    public function addProvider(string $class): self
    {
        $this->guard();
        $this->providers[] = $class;

        return $this;
    }

    /**
     * Устанавливает полный список обычных провайдеров, заменяя предыдущий.
     *
     * @param list<class-string<ServiceProviderInterface>> $classes Класс провайдера
     *
     * @return $this
     *
     * @throws ConfigException Если конфигурация заблокирована для изменений
     */
    public function setProviders(array $classes): self
    {
        $this->guard();
        $this->providers = $classes;

        return $this;
    }

    /**
     * Добавляет класс провайдера в список отложенных (ленивых) провайдеров.
     *
     * @param class-string<ServiceProviderInterface> $class Класс провайдера
     *
     * @return $this
     *
     * @throws ConfigException Если конфигурация заблокирована для изменений
     */
    public function addDeferredProvider(string $class): self
    {
        $this->guard();
        $this->deferredProviders[] = $class;

        return $this;
    }

    /**
     * Устанавливает полный список отложенных провайдеров, заменяя предыдущий.
     *
     * @param list<class-string<ServiceProviderInterface>> $classes Класс провайдера
     *
     * @return $this
     *
     * @throws ConfigException Если конфигурация заблокирована для изменений
     */
    public function setDeferredProviders(array $classes): self
    {
        $this->guard();
        $this->deferredProviders = $classes;

        return $this;
    }

    /**
     * Возвращает список зарегистрированных обычных провайдеров.
     *
     * @return list<class-string<ServiceProviderInterface>>
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * Возвращает список зарегистрированных отложенных провайдеров.
     *
     * @return list<class-string<ServiceProviderInterface>>
     */
    public function getDeferredProviders(): array
    {
        return $this->deferredProviders;
    }

    /**
     * Устанавливает путь к директории с базовыми конфигурациями. Абсолютный или относительно корня проекта.
     *
     * @throws ConfigException Если конфигурация заблокирована для изменений
     */
    public function setBaseConfigPath(string $baseConfigPath): self
    {
        $this->guard();
        $this->baseConfigPath = $baseConfigPath;

        return $this;
    }

    /**
     * Возвращает путь к директории с базовыми конфигурациями. Абсолютный или относительно корня проекта.
     */
    public function getBaseConfigPath(): string
    {
        return $this->baseConfigPath;
    }

    /**
     * Устанавливает путь к директории с ленивыми конфигурациями. Абсолютный или относительно корня проекта.
     *
     * @throws ConfigException Если конфигурация заблокирована для изменений
     */
    public function setLazyConfigPath(string $lazyConfigPath): self
    {
        $this->guard();
        $this->lazyConfigPath = $lazyConfigPath;

        return $this;
    }

    /**
     * Возвращает путь к директории с ленивыми конфигурациями. Абсолютный или относительно корня проекта.
     */
    public function getLazyConfigPath(): string
    {
        return $this->lazyConfigPath;
    }
}
