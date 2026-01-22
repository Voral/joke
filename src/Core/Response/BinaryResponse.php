<?php

namespace Vasoft\Joke\Core\Response;

use Vasoft\Joke\Core\Routing\Exceptions\NotFoundException;

abstract class BinaryResponse extends Response
{
    /**
     * Тело файла
     *
     * @var string
     */
    protected string $body = '';
    public string $filename = '' {
        set(string $value) => $this->filename = basename($value);
        get => $this->filename;
    }

    /**
     * Загрузка тела из файла
     * @param string $filename Полное имя файла
     * @return $this
     * @throws NotFoundException Если не удалось считать файл
     */
    public function load(string $filename): static
    {
        if (
            !file_exists($filename) ||
            ($this->body = file_get_contents($filename)) === false) {
            throw new NotFoundException('File not found');
        }
        if ($this->filename === '') {
            $this->filename = $filename;
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setBody($body): static
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @inheritDoc
     */
    public function getBodyAsString(): string
    {
        return $this->body;
    }

    public function send(): static
    {
        $this->headers->setContentType($this->getContentType());
        $this->headers->set('Content-Length', strlen($this->body));
        $this->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $this->filename));
        return parent::send();
    }

    abstract public function getContentType(): string;
}