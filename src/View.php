<?php

/**
 * This file is part of Laucov's Views project.
 * 
 * Copyright 2024 Laucov Serviços de Tecnologia da Informação Ltda.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @package views
 * 
 * @author Rafael Covaleski Pereira <rafael.covaleski@laucov.com>
 * 
 * @license <http://www.apache.org/licenses/LICENSE-2.0> Apache License 2.0
 * 
 * @copyright © 2024 Laucov Serviços de Tecnologia da Informação Ltda.
 */

namespace Laucov\Views;

/**
 * Outputs a view file.
 */
class View
{
    /**
     * Whether this view is currently caching.
     */
    protected bool $cache = false;

    /**
     * Cache filename.
     */
    protected null|string $cacheCustomPath = null;

    /**
     * View cache files directory.
     */
    protected string $cacheDirectory;

    /**
     * Cache life time in seconds.
     */
    protected int $cacheTtl = 3600;

    /**
     * View files directory.
     */
    protected string $directory;

    /**
     * Parent view name.
     */
    protected null|string $parent = null;

    /**
     * View path.
     */
    protected string $path;

    /**
     * Currently open section.
     */
    protected null|string $section = null;

    /**
     * Currently built sessions.
     * 
     * @var array<string, string[]>
     */
    protected array $sections = [];

    /**
     * Current data in use to generate the view's HTML.
     */
    protected array $temporaryData = [];

    /**
     * Create the view instance.
     */
    public function __construct(
        string $views_directory,
        string $cache_directory,
        string $path,
    ) {
        $this->directory = rtrim($views_directory, '\\/');
        $this->cacheDirectory = rtrim($cache_directory, '\\/');
        $this->path = trim($path, '/');
    }

    /**
     * Activate caching and set its parameters.
     */
    public function cache(int $ttl = 3600, null|string $path = null): static
    {
        $this->cache = true;
        $this->cacheCustomPath = $path;
        $this->cacheTtl = $ttl;
        return $this;
    }

    /**
     * Generate the view content or restore its cache.
     */
    public function generate(array $data = []): string
    {
        // Check cache.
        if ($this->cache) {
            // Check if all files exist.
            $cache_html_fn = $this->getCacheFilename();
            $cache_info_fn = $this->getCacheFilename(true);
            $exists = file_exists($cache_html_fn)
                && file_exists($cache_info_fn);
            if ($exists) {
                // Check if is expired.
                $cache_info = unserialize(file_get_contents($cache_info_fn));
                $is_valid = isset($cache_info['expires'])
                    && time() < $cache_info['expires'];
                // Return valid cache.
                if ($is_valid) {
                    return file_get_contents($cache_html_fn);
                }
            }
        }

        // Create HTML.
        $this->temporaryData = $data;
        $content = $this->createContent();
        $this->temporaryData = [];

        // Cache generated view.
        if ($this->cache) {
            // Store HTML.
            file_put_contents($cache_html_fn, $content);
            // Store information.
            $cache_info = [
                'expires' => time() + $this->cacheTtl,
            ];
            file_put_contents($cache_info_fn, serialize($cache_info));
        }

        return $content;
    }

    /**
     * Close the currently open section.
     */
    protected function closeSection(): string
    {
        $this->sections[$this->section][] = ob_get_clean();
        $this->section = null;
        return '';
    }

    /**
     * Create the view's HTML.
     */
    protected function createContent(): string
    {
        // Get view content.
        ob_start();
        extract($this->temporaryData);
        require $this->getFilename();
        $content = preg_replace('/^\s+/m', '', ob_get_clean());
        $content = preg_replace('/\s+$/m', '', $content);
        // $content = preg_replace('/^\n$/m', '', $content);

        // Check if the view extends a template.
        if ($this->parent !== null) {
            $parent = new View(
                $this->directory,
                $this->cacheDirectory,
                $this->parent,
            );
            $parent->sections = $this->sections;
            $content = $parent->generate() . "\n" . $content;
        }

        return $content;
    }

    /**
     * Extend a view.
     */
    public function extend(string $name): string
    {
        $this->parent = $name;
        return '';
    }

    /**
     * Get the cache filename.
     */
    protected function getCacheFilename($info = false): string
    {
        return $this->cacheDirectory
            . DIRECTORY_SEPARATOR
            . ($this->cacheCustomPath ?? $this->path)
            . ($info ? '.cache' : '.html');
    }

    /**
     * Get the view filename.
     */
    protected function getFilename(): string
    {
        return $this->directory
            . DIRECTORY_SEPARATOR
            . $this->path
            . '.php';
    }

    /**
     * Open a section to append the next output contents.
     */
    protected function openSection(string $name): string
    {
        $this->section = $name;
        ob_start();
        return '';
    }

    /**
     * Print the contents of a section.
     */
    protected function printSection(string $name): string
    {
        return array_key_exists($name, $this->sections)
            ? implode('', $this->sections[$name])
            : '';
    }
}
