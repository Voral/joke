<?php

namespace Vasoft\Joke\Core\Routing;

use Vasoft\Joke\Contract\Core\Routing\RouteInterface;
use Vasoft\Joke\Core\Request\HttpMethod;
use Vasoft\Joke\Core\Request\HttpRequest;
use Vasoft\Joke\Core\ServiceContainer;

class Route implements RouteInterface
{
    /**
     * Массив доступных правил обработки параметров
     *
     * Добавляется через двоеточие после параметра в URI например /catalog/{code:slug}. Используется для построения регулярного выражения
     * @var array<string,string>
     */
    protected array $rules = [
        'default' => '[^/]+',
        'slug' => '[a-z0-9\-_]+',
        'int' => '\d+',
        'id' => '\d+',
    ];

    public ?string $compiledPattern = null {
        get => $this->compiledPattern ??= $this->compilePattern();
    }
    public HttpMethod $method {
        get => $this->method;
    }

    public function __construct(
        private readonly ServiceContainer $serviceContainer,
        private readonly string $path,
        HttpMethod $method,
        private readonly array|object|string $handler,
        private readonly string $name = ''
    ) {
        $this->method = $method;
    }

    protected function compilePattern(): string
    {
        $tokens = preg_split('/(\{[^}]+\})/', $this->path, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $regex = '';

        foreach ($tokens as $token) {
            if (str_starts_with($token, '{') && str_ends_with($token, '}')) {
                $inner = substr($token, 1, -1);

                if (str_contains($inner, ':')) {
                    [$name, $ruleName] = explode(':', $inner, 2);
                } else {
                    $name = $inner;
                    $ruleName = 'default';
                }
                $rule = $this->rules[$ruleName] ?? $this->rules['default'];

                $regex .= "(?P<{$name}>{$rule})";
            } else {
                $regex .= preg_quote($token, '#');
            }
        }
        return '#^' . $regex . '$#i';
    }


    public function withMethod(HttpMethod $method): static
    {
        return new static($this->serviceContainer, $this->path, $method, $this->handler, $this->name);
    }

    public function matches(HttpRequest $request): bool
    {
        $matches = [];
        if (!preg_match($this->compiledPattern, $request->getPath(), $matches)) {
            return false;
        }
        $request->setProps(array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));

        return true;
    }


    public function run(HttpRequest $request): mixed
    {
        if (is_string($this->handler) && !str_contains($this->handler, '::')) {
            if (class_exists($this->handler)) {
                $constructorArgs = $this->serviceContainer->getParameterResolver()
                    ->resolveForConstructor($this->handler, $request->props->getAll());

                $controller = new $this->handler(...$constructorArgs);
                $handler = [$controller, '__invoke'];
                $args = $this->serviceContainer->getParameterResolver()
                    ->resolveForCallable($handler, $request->props->getAll());
                return $controller(...$args);
            }
            $args = $this->serviceContainer->getParameterResolver()
                ->resolveForCallable($this->handler, $request->props->getAll());
            return ($this->handler)(...$args);
        }
        $args = $this->serviceContainer->getParameterResolver()
            ->resolveForCallable($this->handler, $request->props->getAll());


        if ($this->handler instanceof \Closure) {
            return ($this->handler)(...$args);
        }
        if (is_array($this->handler)) {
            [$target, $method] = $this->handler;
            if (is_string($target)) {
                return $target::$method(...$args);
            } else {
                return $target->$method(...$args);
            }
        }
        $args = $this->serviceContainer->getParameterResolver()
            ->resolveForCallable($this->handler, $request->props->getAll());

        [$class, $method] = explode('::', $this->handler, 2);

        return $class::$method(...$args);
    }
}