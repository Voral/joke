<?php

declare(strict_types=1);

namespace Vasoft\Joke\Storage\Exceptions;

use Vasoft\Joke\Exceptions\JokeException;

/**
 * Исключение, выбрасываемое хранилищем при ошибках операций.
 */
final class StorageException extends JokeException {}
