<?php

declare(strict_types=1);

namespace Vasoft\Joke\Storage\Exceptions;

use Vasoft\Joke\Exceptions\JokeException;

/**
 * Базовое исключение слоя хранения данных.
 *
 * Выбрасывается при ошибках чтения/записи файлов, проблемах с блокировками
 * и других сбоях в работе драйверов хранилища.
 */
class StorageException extends JokeException {}