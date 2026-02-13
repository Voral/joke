<?php

declare(strict_types=1);

namespace Vasoft\Joke\Core\Response;

use Vasoft\Joke\Http\Response\HtmlResponse as NewHtmlResponse;

use function Vasoft\Joke\triggerDeprecation;

require_once __DIR__ . '/../../DeprecatedClass.php';
triggerDeprecation(
    'Vasoft\Joke\Core\Response\HtmlResponse',
    'Vasoft\Joke\Http\Response\HtmlResponse',
);

if (false) {
    /**
     * @deprecated since 1.2.0, use \Vasoft\Joke\Http\Response\HtmlResponse instead
     */
    class HtmlResponse extends NewHtmlResponse {}
}
class_alias(NewHtmlResponse::class, __NAMESPACE__ . '\HtmlResponse');
