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
class Builder
{
    /**
     * Parent section indicator.
     */
    protected const PARENT_SECTION = 1;

    /**
     * View files directory.
     */
    protected string $directory;

    /**
     * View filename.
     */
    protected string $filename;

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
    public function __construct(string $directory, string $path)
    {
        $this->directory = rtrim($directory, '\\/');
        $this->path = trim($path, '/');
        $this->filename = $this->directory
            . DIRECTORY_SEPARATOR
            . $this->path . '.php';
    }

    /**
     * Close the currently open section.
     */
    public function closeSection(): string
    {
        $this->sections[$this->section][] = ob_get_clean();
        $this->section = null;
        return '';
    }

    /**
     * Close and print the current open section.
     */
    public function commitSection(): string
    {
        $name = $this->section;
        $this->closeSection();
        return $this->getSection($name);
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
     * Generate the view content or restore its cache.
     */
    public function generate(array $data = []): string
    {
        // Save data and remove the variable name from the scope.
        $this->temporaryData = $data;
        unset($data);

        // Build the view content.
        if (file_exists($this->filename)) {
            extract($this->temporaryData);
            ob_start();
            require $this->filename;
            $content = ob_get_clean();
        } else {
            $message = sprintf('Failed to load view "%s".', $this->path);
            throw new \RuntimeException($message);
        }

        // Check if extends a template.
        if ($this->parent !== null) {
            // Create parent builder.
            $parent = new Builder($this->directory, $this->parent);
            // Resolve all sections.
            $section_names = $this->getSectionNames();
            $sections = array_map([$this, 'resolveSection'], $section_names);
            $sections = array_combine($section_names, $sections);
            $parent->sectionOverrides = $sections;
            // Generate and prepend the parent content.
            $parent_content = $parent->generate($this->temporaryData);
            $content = "{$parent_content}\n{$content}";
        }

        // Remove empty lines and identation.
        $content = preg_replace('/\n+\s*/', "\n", trim($content));

        // Clear temporary data.
        $this->temporaryData = [];

        return $content;
    }

    /**
     * Add the original parent section if overriding.
     */
    public function getParent(): string
    {
        $this->flushSection();
        $this->sections[$this->section][] = static::PARENT_SECTION;
        return '';
    }

    /**
     * Get the contents of a section.
     */
    public function getSection(string $name): string
    {
        $contents = array_filter($this->resolveSection($name), 'is_string');
        return implode('', $contents);
    }

    /**
     * Include a secondary view.
     */
    public function include(
        string $path,
        null|array $data = null,
        bool $merge_data = true,
    ): string {
        // Merge data.
        if ($data !== null && $merge_data) {
            $data = array_replace($this->temporaryData, $data);
        }

        // Output view.
        $view = new Builder($this->directory, $path);
        $content = $view->generate($data ?? $this->temporaryData);

        return "\n{$content}\n";
    }

    /**
     * Open a section to append the next output contents.
     */
    public function openSection(string $name): string
    {
        $this->section = $name;
        ob_start();
        return '';
    }

    /**
     * Append the current output buffer to the active section.
     */
    protected function flushSection(): string
    {
        $this->sections[$this->section][] = ob_get_clean();
        ob_start();
        return '';
    }

    /**
     * Get all registered section names.
     * 
     * @var array<string>
     */
    protected function getSectionNames(): array
    {
        return array_unique([
            ...array_keys($this->sectionOverrides),
            ...array_keys($this->sections),
        ]);
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
