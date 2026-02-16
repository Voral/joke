<?php

declare(strict_types=1);

namespace Vasoft\Joke\Logging;

use Vasoft\Joke\Contract\Logging\LoggerInterface;

/**
 * Уровни логирования, соответствующие стандарту PSR-3 и RFC 5424 (syslog).
 *
 * Каждый уровень представляет степень серьёзности события: от отладочной информации (DEBUG)
 * до аварийной ситуации (EMERGENCY).
 *
 * Используется в связке с {@see LoggerInterface} для определения важности записываемого
 * сообщения.
 *
 * Числовая серьёзность каждого уровня доступна через метод {@see self::severity()}.
 * Чем выше значение, тем критичнее событие (DEBUG = 100, EMERGENCY = 800).
 */
enum LogLevel: string
{
    /** Аварийная ситуация - система не работоспособна */
    case EMERGENCY = 'emergency';
    /** Тревога - требуется срочная реакция */
    case ALERT = 'alert';
    /** Критическая ошибка - недоступна важная система */
    case CRITICAL = 'critical';
    /** Ошибка - не требует срочного вмешательства */
    case ERROR = 'error';
    /** Предупреждение - возможна потенциальная ошибка */
    case WARNING = 'warning';

    /** Уведомление - нормальные события, но требующие внимания */
    case NOTICE = 'notice';
    /** Информация - информация о работе приложения */
    case INFO = 'info';

    /** Одладочная информация */
    case DEBUG = 'debug';

    /**
     * Возвращает числовое значение серьёзности уровня логирования.
     *
     * Чем выше значение — тем выше критичность события:
     * - DEBUG = 100 (наименее критичный)
     * - EMERGENCY = 800 (наиболее критичный)
     *
     * Используется для сравнения уровней и фильтрации сообщений в обработчиках логирования
     * (например, записывать только события с серьёзностью >= 400).
     *
     * @return int Числовое значение серьёзности (от 100 до 800)
     */
    public function severity(): int
    {
        return match ($this) {
            LogLevel::DEBUG => 100,
            LogLevel::INFO => 200,
            LogLevel::NOTICE => 300,
            LogLevel::WARNING => 400,
            LogLevel::ERROR => 500,
            LogLevel::CRITICAL => 600,
            LogLevel::ALERT => 700,
            LogLevel::EMERGENCY => 800,
        };
    }
}
