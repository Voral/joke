<?php

namespace Vasoft\Joke\Core\Response;

use Vasoft\Joke\Core\Collections\HeadersCollection;

/**
 * Абстрактный базовый класс HTTP-ответа.
 *
 * Определяет общую структуру и поведение всех типов ответов (HTML, JSON и др.).
 * Управляет HTTP-статусом, заголовками и отправкой тела ответа клиенту.
 * Конкретные реализации должны определять формат тела ответа через абстрактные методы.
 */
abstract class Response
{
    /**
     * HTTP-статус ответа.
     *
     * По умолчанию: OK (200).
     *
     * @var ResponseStatus
     */
    public ResponseStatus $status {
        get => $this->status ??= ResponseStatus::OK;
    }
    /**
     * Коллекция HTTP-заголовков ответа.
     *
     * Лениво инициализируется при первом обращении.
     *
     * @var HeadersCollection|null
     */
    public ?HeadersCollection $headers = null {
        get {
            return $this->headers ??= new HeadersCollection([]);
        }
    }

    public function __construct() { }

    /**
     * Устанавливает тело ответа.
     *
     * Конкретная реализация определяет допустимые типы входных данных
     * (например, строка для HtmlResponse, массив для JsonResponse).
     *
     * @param string|int|float|bool|array|object|null $body Тело ответа
     * @return static
     */
    abstract public function setBody($body): static;

    /**
     * Возвращает текущее тело ответа.
     *
     * Тип возвращаемого значения зависит от реализации.
     *
     * @return mixed
     */
    abstract public function getBody(): mixed;

    /**
     * Отправляет ответ клиенту.
     *
     * Выполняет следующие действия:
     * 1. Отправляет все установленные HTTP-заголовки
     * 2. Отправляет тело ответа через echo
     *
     * @return static
     */
    public function send(): static
    {
        $this->sendHeaders();
        echo $this->getBodyAsString();
        return $this;
    }

    /**
     * Возвращает строковое представление тела ответа.
     *
     * Используется при отправке ответа. Должен быть реализован
     * с учётом специфики формата (например, json_encode для JSON).
     *
     * @return string
     */
    abstract public function getBodyAsString(): string;

    /**
     * Отправляет HTTP-заголовки клиенту.
     *
     * Включает все пользовательские заголовки и строку статуса HTTP.
     * Вызывается автоматически методом send().
     *
     * @return void
     */
    protected function sendHeaders(): void
    {
        $headers = $this->headers->getAll();
        foreach ($headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }
        header('HTTP/1.1 ' . $this->status->value . ' ' . $this->status->http());
    }

    /**
     * Устанавливает HTTP-статус ответа.
     *
     * @param ResponseStatus $status Статус из перечисления ResponseStatus
     * @return static
     */
    public function setStatus(ResponseStatus $status): static
    {
        $this->status = $status;
        return $this;
    }
}