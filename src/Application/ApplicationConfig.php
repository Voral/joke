<?php

declare(strict_types=1);

namespace Vasoft\Joke\Application;

use Vasoft\Joke\Config\AbstractConfig;
use Vasoft\Joke\Config\Exceptions\ConfigException;
use Vasoft\Joke\Http\Response\HtmlResponse;

class ApplicationConfig extends AbstractConfig
{
    /**
     * @var ''|class-string Тип ответа по умолчанию
     */
    private string $responseClass = '';
    private string $fileRoues = 'routes/web.php';

    /**
     * Устанавливает путь к файлу роутов абсолютный или относительно корня проекта.
     *
     * @param string $fileRoues путь к файлу роутов
     *
     * @return $this
     *
     * @throws ConfigException
     */
    public function setFileRoues(string $fileRoues): static
    {
        $this->guard();
        $this->fileRoues = $fileRoues;

        return $this;
    }

    /**
     * Возвращает путь к файлу роутов.
     */
    public function getFileRoues(): string
    {
        return $this->fileRoues;
    }

    /**
     * @return ''|class-string
     */
    public function getResponseClass(): string
    {
        return $this->responseClass;
    }

    /**
     * Устанавливает тип ответа по умолчанию для всего приложения.
     *
     * По умолчанию включено авто-определение типа (массив -> JsonResponse, остальное -> HtmlResponse).
     * Для включения строгого режима передайте имя класса (например, JsonReponse::class).
     *
     * @param ''|class-string $responseClass Тип ответа по умолчанию
     *
     * @return $this
     *
     * @throws ConfigException
     */
    public function setResponseClass(string $responseClass): static
    {
        $this->guard();
        $this->responseClass = $responseClass;

        return $this;
    }
}
