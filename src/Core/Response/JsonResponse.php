<?php

namespace Vasoft\Joke\Core\Response;

use JsonException;

/**
 * HTTP-ответ в формате JSON.
 *
 * Автоматически устанавливает заголовок Content-Type: application/json
 * и преобразует переданные данные в JSON при отправке.
 * Поддерживает только массивы и объекты, совместимые с json_encode().
 */
class JsonResponse extends Response
{
    /**
     * Тело ответа в виде массива (или объекта, совместимого с json_encode).
     *
     * @var array
     */
    protected array $body = [];

    /**
     * Конструктор JSON-ответа.
     *
     * Устанавливает Content-Type в 'application/json'.
     */
    public function __construct()
    {
        parent::__construct();
        $this->headers->setContentType('application/json');
    }

    /**
     * Устанавливает тело ответа.
     *
     * Ожидается массив или объект, который может быть сериализован в JSON.
     * Несовместимые типы (например, ресурсы) вызовут JsonException при отправке.
     *
     * @param array $body Данные для сериализации в JSON
     * @return static
     */
    public function setBody($body): static
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Возвращает текущее тело ответа как массив.
     *
     * @return array
     */
    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * Сериализует тело ответа в строку JSON.
     *
     * Использует флаг JSON_THROW_ON_ERROR — при ошибке сериализации
     * будет выброшено исключение JsonException.
     *
     * @return string Строка в формате JSON
     * @throws JsonException Если данные не могут быть сериализованы в JSON
     * @todo Преобразовать в исключение типа JokeException
     */
    public function getBodyAsString(): string
    {
        return json_encode($this->body, JSON_THROW_ON_ERROR);
    }
}