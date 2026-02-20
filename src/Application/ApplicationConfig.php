<?php

declare(strict_types=1);

namespace Vasoft\Joke\Application;

use Vasoft\Joke\Config\AbstractConfig;
use Vasoft\Joke\Config\Exceptions\ConfigException;

class ApplicationConfig extends AbstractConfig
{
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
}
