<?php

declare(strict_types=1);

namespace Vasoft\Joke\Logging\Handlers;

use Vasoft\Joke\Contract\Logging\LogHandlerInterface;
use Vasoft\Joke\Contract\Logging\MessageFormatterInterface;
use Vasoft\Joke\Logging\Exception\LogException;
use Vasoft\Joke\Logging\LogLevel;

/**
 * Обработчик логирования, записывающий сообщения в поток (файл, stdout, stderr и т.п.).
 *
 * Поддерживает фильтрацию по минимальному уровню серьёзности.  Открывает поток в бинарном режиме ('ab'),
 * чтобы избежать неожиданного преобразования символов новой строки на Windows.
 *
 * Ресурс потока автоматически закрывается при уничтожении объекта.
 */
class StreamHandler implements LogHandlerInterface
{
    /**
     * Открытый ресурс потока (например, файловый дескриптор).
     *
     * @var resource
     */
    private $stream;
    /**
     * Минимальная числовая серьёзность уровня, при которой сообщение будет записано.
     *
     * Сообщения с уровнем ниже этого значения игнорируются.
     */
    private readonly int $minSeverity;
    private readonly int $maxSeverity;

    /**
     * Создаёт обработчик логирования с указанным потоком и диапазоном уровней.
     *
     * Сообщения записываются только если их уровень серьёзности находится в диапазоне между переданными уровнями
     * (включительно). Порядок аргументов не важен: минимальное и максимальное значения определяются автоматически
     * на основе числовых значений серьёзности.
     *
     * По умолчанию логируются все уровни: от DEBUG (100) до EMERGENCY (800).
     *
     * Если указан путь к файлу и его размер превышает лимит $maxFileSize, файл переименовывается в "<путь>.old"
     * (старый архив удаляется без предупреждения).
     *
     * Опционально можно задать собственный форматтер. Если он указан и в контексте есть ключ `'rawMessage'`,
     * хендлер будет использовать его для повторной интерполяции — это позволяет применять специфичный формат
     * независимо от глобального форматтера логгера.
     *
     * @param string                         $stream      Путь к файлу или URI потока (например, 'php://stderr', 'php://stdout')
     * @param LogLevel                       $minLogLevel Один из граничных уровней диапазона (по умолчанию — DEBUG)
     * @param LogLevel                       $maxLogLevel Второй граничный уровень диапазона (по умолчанию — EMERGENCY)
     * @param int                            $maxFileSize Максимальный размер файла в байтах перед ротацией (по умолчанию — 10 МБ)
     * @param null|MessageFormatterInterface $formatter   Опциональный форматтер для переинтерполяции `rawMessage`
     *
     * @throws LogException Если не удаётся создать директорию, открыть поток или передан недопустимый тип `$stream`
     */
    public function __construct(
        string $stream,
        LogLevel $minLogLevel = LogLevel::DEBUG,
        LogLevel $maxLogLevel = LogLevel::EMERGENCY,
        int $maxFileSize = 10_000_000,
        private readonly ?MessageFormatterInterface $formatter = null,
    ) {
        $dir = dirname($stream);
        if (!is_dir($dir) && !mkdir($dir, 0o775, true) && !is_dir($dir)) {
            throw new LogException("Unable to create directory '{$dir}'.");
        }
        if (file_exists($stream) && filesize($stream) > $maxFileSize) {
            rename($stream, $stream . '.old');
        }
        $this->stream = fopen($stream, 'ab');
        if (false === $this->stream) {
            throw new LogException("Unable to open '{$stream}'.");
        }
        $min = $minLogLevel->severity();
        $max = $maxLogLevel->severity();
        $this->minSeverity = min($min, $max);
        $this->maxSeverity = max($min, $max);
    }

    /**
     * {@inheritDoc}
     *
     * Записывает сообщение в поток, если его уровень серьёзности попадает в разрешённый диапазон.
     * Формат записи по умолчанию: "Y-m-d H:i:s [level] message".
     *
     * Если задан собственный форматтер и в `$context` присутствует `'rawMessage'`,
     * используется именно он для генерации финального сообщения.
     * В противном случае применяется сообщение, уже обработанное логгером.
     */
    public function write(LogLevel $level, string $message, array $context = []): void
    {
        if ($this->minSeverity > $level->severity() || $this->maxSeverity < $level->severity()) {
            return;
        }
        if (null !== $this->formatter && array_key_exists('rawMessage', $context)) {
            $message = $this->formatter->interpolate($context['rawMessage']);
        }

        fwrite(
            $this->stream,
            sprintf("%s [%s] %s\n", date('Y-m-d H:i:s'), $level->name, $message),
        );
    }

    public function __destruct()
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }
}
