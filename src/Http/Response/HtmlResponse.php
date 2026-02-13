<?php

declare(strict_types=1);

namespace Vasoft\Joke\Http\Response;

use Vasoft\Joke\Http\Response\Response as NewResponse;

/**
 * HTTP-ответ в формате HTML.
 *
 * Автоматически устанавливает заголовок Content-Type: text/html.
 * Принимает любые данные и преобразует их в строку при установке тела ответа.
 */
class HtmlResponse extends NewResponse
{
    /**
     * Тело HTML-ответа.
     */
    protected string $body = '';

    /**
     * Конструктор HTML-ответа.
     *
     * Устанавливает Content-Type в 'text/html'.
     */
    public function __construct()
    {
        parent::__construct();
        $this->headers->setContentType('text/html');
    }

    /**
     * Устанавливает тело ответа.
     *
     * Любое переданное значение автоматически приводится к строке.
     * Для объектов вызывается метод __toString(), если он реализован.
     *
     * @param mixed $body Данные для отправки в теле ответа
     */
    public function setBody(mixed $body): static
    {
        $this->body = (string) $body;

        return $this;
    }

    /**
     * Возвращает текущее тело ответа.
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Возвращает строковое представление тела ответа.
     *
     * Для HTML-ответа тело уже хранится в виде строки, поэтому метод
     * просто возвращает его без дополнительной обработки.
     */
    public function getBodyAsString(): string
    {
        return $this->body;
    }
}
