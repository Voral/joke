<?php

declare(strict_types=1);

namespace Vasoft\Joke\Provider\Exceptions;

use Vasoft\Joke\Exceptions\JokeException;

/**
 * Исключение, выбрасываемое при ошибках конфигурации, сборки или выполнения сервис-провайдеров.
 */
class ProviderException extends JokeException {}
