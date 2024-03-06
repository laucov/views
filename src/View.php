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
     * Parent section indicator.
     */
    protected const PARENT_SECTION = 1;

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
     * Initial output buffer level.
     * 
     * Registered everytime `createContent()` is called.
     */
    protected int $obLevel = 0;

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
     * Sections built by child views.
     * 
     * @var array<string, string[]>
     */
    protected array $sectionOverrides = [];

    /**
     * Currently built sections.
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
     * Close and print the current open section.
     */
    protected function commitSection(): string
    {
        $name = $this->section;
        $this->closeSection();
        return $this->getSection($name);
    }

    /**
     * Create the view's HTML.
     */
    protected function createContent(): string
    {
        // Get view content.
        $this->obLevel = ob_get_level();
        ob_start();
        extract($this->temporaryData);
        require $this->getFilename();
        $content = preg_replace('/^\s+/m', '', ob_get_clean());
        $content = preg_replace('/\s+$/m', '', $content);

        // Check if the view extends a template.
        if ($this->parent !== null) {
            $parent = new View(
                $this->directory,
                $this->cacheDirectory,
                $this->parent,
            );
            $section_keys = array_unique([
                ...array_keys($this->sections),
                ...array_keys($this->sectionOverrides),
            ]);
            $sections = array_map([$this, 'resolveSection'], $section_keys);
            $sections = array_combine($section_keys, $sections);
            $parent->sectionOverrides = $sections;
            $parent_content = $parent->generate($this->temporaryData);
            $content = strlen($content) > 0
                ? "{$parent_content}\n{$content}"
                : $parent_content;
        }

        return $content;
    }

    /**
     * Extend a view.
     */
    public function extend(string $path): string
    {
        $this->parent = $path;
        return '';
    }

    /**
     * Append the current output buffer to the active section.
     */
    public function flushSection(): string
    {
        $this->sections[$this->section][] = ob_get_clean();
        ob_start();
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
     * Add the original parent section if overriding.
     */
    protected function getParent(): string
    {
        $this->flushSection();
        $this->sections[$this->section][] = static::PARENT_SECTION;
        return '';
    }

    protected function include(
        string $path,
        null|array $data = null,
        bool $merge_data = true,
    ): string {
        // Merge data.
        if ($data !== null && $merge_data) {
            $data = array_replace($this->temporaryData, $data);
        }

        // Output view.
        $view = new View(
            $this->directory,
            $this->cacheDirectory,
            $path,
        );

        return $view->generate($data ?? $this->temporaryData) . PHP_EOL;
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
     * Get the contents of a section.
     */
    protected function getSection(string $name): string
    {
        $contents = array_filter($this->resolveSection($name), 'is_string');
        return implode('', $contents);
    }

    /**
     * Resolve all placeholders from a section.
     * 
     * @return array<string>
     */
    protected function resolveSection(string $name): array
    {
        // Get the current section content.
        $section = $this->sections[$name] ?? [];

        // Apply override.
        if (array_key_exists($name, $this->sectionOverrides)) {
            // Get override parts.
            $override = $this->sectionOverrides[$name];
            // Merge override with parent section.
            $result = [];
            foreach ($override as $part) {
                if ($part === static::PARENT_SECTION) {
                    array_push($result, ...$section);
                } else {
                    $result[] = $part;
                }
            }
            return $result;
        } else {
            return $section;
        }
    }
}
