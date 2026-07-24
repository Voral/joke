<?php

declare(strict_types=1);

/**
 * Вспомогательные функции для фреймворка Joke.
 */

if (!function_exists('storage_path')) {
    /**
     * Генерирует абсолютный путь к директории storage.
     *
     * @param string $path Подпуть внутри storage (например, 'ratelimit')
     *
     * @return string Полный путь
     */
    function storage_path(string $path = ''): string
    {
        // Пытаемся определить корневую директорию проекта
        $root = dirname(__DIR__, 2);
        
        $storagePath = $root . '/storage';
        
        if ($path !== '') {
            $storagePath .= '/' . $path;
        }
        
        return $storagePath;
    }
}

if (!function_exists('config_path')) {
    /**
     * Генерирует абсолютный путь к директории config.
     *
     * @param string $path Подпуть внутри config
     *
     * @return string Полный путь
     */
    function config_path(string $path = ''): string
    {
        $root = dirname(__DIR__, 2);
        
        $configPath = $root . '/config';
        
        if ($path !== '') {
            $configPath .= '/' . $path;
        }
        
        return $configPath;
    }
}
