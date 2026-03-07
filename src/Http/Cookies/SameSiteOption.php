<?php

declare(strict_types=1);

namespace Vasoft\Joke\Http\Cookies;

/**
 * Перечисление допустимых значений атрибута SameSite для HTTP cookie.
 *
 * Определяет политику браузера по отправке cookie при кросс-сайтовых запросах.
 * Используется для защиты от CSRF-атак (Cross-Site Request Forgery).
 *
 * @see https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-rfc6265bis#section-4.1.2.7
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie/SameSite
 */
enum SameSiteOption: string
{
    /**
     * Строгий режим (Strict).
     *
     * Cookie отправляется только в рамках сайта первого лица (same-site).
     * Не отправляется при переходах по ссылкам с внешних сайтов, даже если метод GET.
     * Обеспечивает максимальную защиту от CSRF, но может ухудшить пользовательский опыт
     * при переходе из почты, мессенджеров или поисковиков (пользователь окажется неавторизованным).
     */
    case Strict = 'Strict';
    /**
     * Умеренный режим (Lax).
     *
     * Cookie не отправляется при кросс-сайтовых запросах, изменяющих состояние (POST, PUT, DELETE).
     * Отправляется при безопасных кросс-сайтовых переходах (GET, переход по ссылке).
     */
    case Lax = 'Lax';
    /**
     * Режим без ограничений (None).
     *
     * Cookie отправляется при всех кросс-сайтовых запросах.
     */
    case None = 'None';
}
