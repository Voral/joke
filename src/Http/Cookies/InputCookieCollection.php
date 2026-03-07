<?php

declare(strict_types=1);

namespace Vasoft\Joke\Http\Cookies;

use Vasoft\Joke\Collections\PropsCollection;

class InputCookieCollection extends PropsCollection
{
    public function __construct(array $props)
    {
        parent::__construct(array_map('urldecode', $props));
    }
}
