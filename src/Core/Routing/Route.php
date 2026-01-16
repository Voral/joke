<?php

namespace Vasoft\Joke\Core\Routing;

use Vasoft\Joke\Core\Request\HttpMethod;
use Vasoft\Joke\Core\Request\HttpRequest;
use Vasoft\Joke\Core\ServiceContainer;

class Route
{
    protected array $rules = [
        'default' => '[^/]+',
        'slug' => '[a-z0-9\-_]+',
        'int' => '\d+',
        'id' => '\d+',
    ];

    public ?string $compiledPattern = null {
        get => $this->compiledPattern ??= $this->compilePattern();
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

    public function __construct(
        private readonly ServiceContainer $serviceContainer,
        public readonly string $path,
        public readonly HttpMethod $method,
        public $handler,
        public readonly string $name = ''
    ) {
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
        $args = new ParameterResolver($this->serviceContainer)
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

        if (is_string($this->handler) && !str_contains($this->handler, '::')) {
            return ($this->handler)(...$args);
        }

        [$class, $method] = explode('::', $this->handler, 2);
        return $class::$method(...$args);
    }
}