<?php

declare(strict_types=1);

namespace Vasoft\Joke\Http\Cookies;

use Vasoft\Joke\Http\Cookies\Exceptions\CookieException;

/**
 * Представляет HTTP cookie как неизменяемый объект данных (DTO).
 *
 * Класс инкапсулирует параметры cookie, выполняет валидацию имен и путей,
 * а также отвечает за корректное кодирование значения при формировании
 * строки заголовка Set-Cookie согласно RFC 6265.
 *
 * @see https://datatracker.ietf.org/doc/html/rfc6265
 */
readonly class Cookie
{
    /**
     * Имя cookie. Содержит только допустимые символы (a-zA-Z0-9_-).
     */
    public string $name;
    /**
     * Путь области видимости cookie на сервере.
     * Всегда начинается с '/' или равен null (по умолчанию).
     */
    public ?string $path;
    /**
     * Домен, для которого действительна cookie.
     */
    public ?string $domain;

    /**
     * Создает новый экземпляр cookie с валидацией параметров.
     *
     * @param string         $name     Имя cookie. Допустимы только латинские буквы, цифры, '_' и '-'.
     * @param string         $value    значение cookie (любые строковые данные)
     * @param null|int       $lifetime Время жизни в секундах. Null для сессионной cookie.
     * @param null|string    $path     Путь области видимости. По умолчанию берется из конфига или null.
     * @param null|string    $domain   Домен области видимости. Null для текущего хоста.
     * @param null|bool      $secure   требовать безопасное соединение (HTTPS)
     * @param bool           $httpOnly Запретить доступ через JS. По умолчанию true.
     * @param SameSiteOption $sameSite Политика отправки при кросс-сайтовых запросах. По умолчанию Lax.
     *
     * @throws CookieException если имя, путь или домен содержат недопустимые символы
     *                         или нарушают формат спецификации
     */
    public function __construct(
        string $name,
        public string $value,
        public ?int $lifetime = null,
        ?string $path = null,
        ?string $domain = null,
        public ?bool $secure = null,
        public bool $httpOnly = true,
        public SameSiteOption $sameSite = SameSiteOption::Lax,
    ) {
        $this->name = $this->normalizeName($name);
        $this->path = null !== $path ? $this->normalizePath($path) : null;
        $this->domain = null !== $domain ? $this->normalizeDomain($domain) : null;
    }

    /**
     * Нормализует и валидирует путь cookie.
     *
     * Добавляет ведущий слэш, удаляет лишние слэши и проверяет наличие
     * запрещенных символов (; , пробел, переводы строк).
     *
     * @param string $path исходная строка пути
     *
     * @return string нормализованный путь (например, '/admin')
     *
     * @throws CookieException если путь содержит недопустимые символы
     */
    private function normalizePath(string $path): string
    {
        $path = '/' . ltrim(trim($path), '/');
        if (preg_match('#[;,\r \n]#', $path)) {
            throw new CookieException("{$path} is not a valid path.");
        }

        return $path;
    }

    /**
     * Нормализует и валидирует домен cookie.
     *
     * Проверяет отсутствие пробелов, точек с запятой и управляющих символов.
     *
     * @param string $domain исходная строка домена
     *
     * @return string очищенная строка домена
     *
     * @throws CookieException если домен пуст или содержит недопустимые символы
     */
    private function normalizeDomain(string $domain): string
    {
        $domain = trim($domain);
        if (preg_match('#[\s;,\r\n]#', $domain)) {
            throw new CookieException("{$domain} is not a valid domain.");
        }
        if ('' === $domain) {
            throw new CookieException('Domain cannot be empty.');
        }

        return $domain;
    }

    /**
     * Валидирует имя cookie.
     *
     * Имя должно соответствовать строгому набору символов (токен RFC).
     *
     * @param string $name исходное имя
     *
     * @return string trimmed имя
     *
     * @throws CookieException если имя содержит недопустимые символы
     */
    private function normalizeName(string $name): string
    {
        $name = trim($name);
        if (0 === preg_match('#^[a-zA-Z0-9_-]+$#', $name)) {
            throw new CookieException("{$name} is not a valid cookie name.");
        }

        return $name;
    }

    /**
     * Генерирует значение заголовка Set-Cookie.
     *
     * Формирует строку формата "Name=Value; Attribute1; Attribute2...",
     * автоматически кодируя значение через rawurlencode() и рассчитывая
     * дату истечения срока действия.
     *
     * @return string полная строка для заголовка HTTP Set-Cookie
     */
    public function headerValue(): string
    {
        $parts = [
            "{$this->name}=" . rawurlencode($this->value),
        ];
        if (null !== $this->lifetime) {
            $timestamp = $this->lifetime <= 0 ? time() - 3600 : time() + $this->lifetime;
            $parts[] = 'Expires=' . gmdate('D, d M Y H:i:s T', $timestamp);
        }
        if (null !== $this->path) {
            $parts[] = 'Path=' . $this->path;
        }
        if (null !== $this->domain) {
            $parts[] = 'Domain=' . $this->domain;
        }
        if ($this->secure || SameSiteOption::None === $this->sameSite) {
            $parts[] = 'Secure';
        }
        if ($this->httpOnly) {
            $parts[] = 'HttpOnly';
        }
        $parts[] = 'SameSite=' . $this->sameSite->value;

        return implode('; ', $parts);
    }
}
