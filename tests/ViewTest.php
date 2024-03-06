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

declare(strict_types=1);

namespace Tests;

use Laucov\Views\View;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Laucov\Views\View
 */
class ViewTest extends TestCase
{
    /**
     * @covers ::cache
     * @covers ::generate
     * @covers ::getCacheFilename
     * @uses Laucov\Views\View::__construct
     * @uses Laucov\Views\View::createContent
     * @uses Laucov\Views\View::getFilename
     */
    public function testCanCache(): void
    {
        // Create view.
        $view = new View(
            __DIR__ . '/view-files',
            __DIR__ . '/view-cache',
            'view-a',
        );
        
        // Generate with cache.
        $cache = __DIR__ . '/view-cache/view-a.html';
        $actual = $view
            ->cache()
            ->generate(['name' => 'Mary']);
        $expected_a = <<<HTML
            <main>
            <p>Hello, Mary!</p>
            </main>
            HTML;
        $this->assertSame($expected_a, $actual);

        // Test if cached.
        $this->assertFileExists($cache);
        $this->assertSame($expected_a, $view->generate(['name' => 'John']));

        // Test default TTL.
        \Laucov\Views\Time::$time = 3600;
        $expected_b = <<<HTML
            <main>
            <p>Greetings, Stranger!</p>
            </main>
            HTML;
        $this->assertSame($expected_b, $view->generate());

        // Cache with custom TTL and name.
        $view->cache(30, 'view-a-custom');
        $expected_b = <<<HTML
            <main>
            <p>Hello, Harry!</p>
            </main>
            HTML;
        $this->assertSame($expected_b, $view->generate(['name' => 'Harry']));
        \Laucov\Views\Time::$time = 3630;
        $expected_c = <<<HTML
            <main>
            <p>Hello, Jane!</p>
            </main>
            HTML;
        $this->assertSame($expected_c, $view->generate(['name' => 'Jane']));
    }

    /**
     * @covers ::closeSection
     * @covers ::commitSection
     * @covers ::createContent
     * @covers ::extend
     * @covers ::flushSection
     * @covers ::getParent
     * @covers ::getSection
     * @covers ::openSection
     * @covers ::resolveSection
     * @uses Laucov\Views\View::__construct
     * @uses Laucov\Views\View::generate
     * @uses Laucov\Views\View::getFilename
     */
    public function testCanExtend(): void
    {
        // Test child view.
        $view = new View(
            __DIR__ . '/view-files',
            __DIR__ . '/view-cache',
            'view-c',
        );
        $expected = <<<HTML
            <header>
            <h1>Article title</h1>
            <p>This is an excerpt.</p>
            </header>
            <main>
            <p>This is a body.</p>
            </main>
            <p>Some final note.</p>
            HTML;
        $this->assertSame($expected, $view->generate());

        // Test grandchild view.
        $view = new View(
            __DIR__ . '/view-files',
            __DIR__ . '/view-cache',
            'view-d',
        );
        $expected = <<<HTML
            <header>
            <h1>My title</h1>
            <p>This is an excerpt.</p>
            </header>
            <main>
            <p>Prepend some content.</p>
            <p>This is a body.</p>
            <p>Append some content.</p>
            </main>
            <p>Some final note.</p>
            HTML;
        $this->assertSame($expected, $view->generate(['title' => 'My title']));
    }

    /**
     * @covers ::include
     * @uses Laucov\Views\View::__construct
     * @uses Laucov\Views\View::createContent
     * @uses Laucov\Views\View::generate
     * @uses Laucov\Views\View::getFilename
     */
    public function testCanInclude(): void
    {
        // Test view.
        $view = new View(
            __DIR__ . '/view-files',
            __DIR__ . '/view-cache',
            'view-e',
        );
        $expected = <<<HTML
            <p>Include with inherited data:</p>
            <pre>a=foo, b=bar</pre>
            <p>Include with custom merged data:</p>
            <pre>a=foo, b=hello, c=baz</pre>
            <p>Include with custom data without merging:</p>
            <pre>b=hello, c=baz</pre>
            HTML;
        $this->assertSame($expected, $view->generate([
            'a' => 'foo',
            'b' => 'bar',
        ]));
    }

    /**
     * @covers ::__construct
     * @covers ::createContent
     * @covers ::generate
     * @covers ::getFilename
     */
    public function testCanOutputWithData(): void
    {
        // Generate without data.
        $view = new View(
            __DIR__ . '/view-files',
            __DIR__ . '/view-cache',
            'view-a',
        );
        $expected_a = <<<HTML
            <main>
            <p>Greetings, Stranger!</p>
            </main>
            HTML;
        $this->assertSame($expected_a, $view->generate());

        // Generate with data.
        $expected_b = <<<HTML
            <main>
            <p>Hello, John!</p>
            </main>
            HTML;
        $this->assertSame($expected_b, $view->generate(['name' => 'John']));

        // Ensure views don't get reserved data.
        $view = new View(
            __DIR__ . '/view-files',
            __DIR__ . '/view-cache',
            'view-b',
        );
        $expected_c = <<<HTML
            <p>Data keys: []</p>
            HTML;
        $this->assertSame($expected_c, $view->generate());
    }

    protected function setUp(): void
    {
        array_map('unlink', glob(__DIR__ . '/view-cache/**'));
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob(__DIR__ . '/view-cache/**'));
    }
}

namespace Laucov\Views;

class Time
{
    public static int $time = 0;
}

function time(): int
{
    return Time::$time;
}
