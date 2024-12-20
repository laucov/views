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

use Laucov\Views\Builder;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Laucov\Views\Builder
 */
class BuilderTest extends TestCase
{
    public function pathProvider(): array
    {
        return [
            ['view-h', '<p>Hello, World!</p>'],
            ['/view-h', '<p>Hello, World!</p>'],
            ['/view-h/', '<p>Hello, World!</p>'],
            ['view-h/', '<p>Hello, World!</p>'],
            ['subfolder/view-j', '<p>Hello, Earth!</p>'],
            ['/subfolder/view-j', '<p>Hello, Earth!</p>'],
            ['/subfolder/view-j/', '<p>Hello, Earth!</p>'],
            ['subfolder/view-j/', '<p>Hello, Earth!</p>'],
            ['subfolder/subsubfolder/view-k', '<p>Hello, Planet!</p>'],
            ['/subfolder/subsubfolder/view-k', '<p>Hello, Planet!</p>'],
            ['/subfolder/subsubfolder/view-k/', '<p>Hello, Planet!</p>'],
            ['subfolder/subsubfolder/view-k/', '<p>Hello, Planet!</p>'],
        ];
    }

    /**
     * @covers ::close
     * @covers ::commit
     * @covers ::extend
     * @covers ::flushSection
     * @covers ::build
     * @covers ::super
     * @covers ::commit
     * @covers ::getSectionNames
     * @covers ::open
     * @covers ::resolveSection
     * @uses Laucov\Views\Builder::__construct
     */
    public function testCanExtend(): void
    {
        // Test child view.
        $view = new Builder(__DIR__ . '/view-files', 'view-c');
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
        $this->assertSame($expected, $view->build());

        // Test grandchild view.
        $view = new Builder(__DIR__ . '/view-files', 'view-d');
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
        $this->assertSame($expected, $view->build(['title' => 'My title']));
    }

    /**
     * @covers ::include
     * @uses Laucov\Views\Builder::__construct
     * @uses Laucov\Views\Builder::build
     */
    public function testCanInclude(): void
    {
        // Test view.
        $view = new Builder(__DIR__ . '/view-files', 'view-e');
        $expected = <<<HTML
            <p>Include with inherited data:</p>
            <pre>a=foo, b=bar</pre>
            <p>Include with custom merged data:</p>
            <pre>a=foo, b=hello, c=baz</pre>
            <p>Include with custom data without merging:</p>
            <pre>b=hello, c=baz</pre>
            HTML;
        $this->assertSame($expected, $view->build([
            'a' => 'foo',
            'b' => 'bar',
        ]));
    }

    /**
     * @covers ::__construct
     * @covers ::build
     */
    public function testCanSetData(): void
    {
        // Generate without data.
        $view = new Builder(__DIR__ . '/view-files', 'view-a');
        $expected_a = <<<HTML
            <main>
            <p>Greetings, Stranger!</p>
            </main>
            HTML;
        $this->assertSame($expected_a, $view->build());

        // Generate with data.
        $expected_b = <<<HTML
            <main>
            <p>Hello, John!</p>
            </main>
            HTML;
        $this->assertSame($expected_b, $view->build(['name' => 'John']));

        // Ensure views don't get reserved data.
        $view = new Builder(__DIR__ . '/view-files', 'view-b');
        $expected_c = <<<HTML
            <p>Data keys: []</p>
            HTML;
        $this->assertSame($expected_c, $view->build());
    }

    /**
     * @covers ::build
     * @uses Laucov\Views\Builder::__construct
     */
    public function testThrowsExceptionsForMissingViews(): void
    {
        $this->expectException(\RuntimeException::class);
        $view = new Builder(__DIR__ . '/view-files', 'view-z');
        $view->build();
    }

    /**
     * @covers ::__construct
     * @uses Laucov\Views\Builder::build
     * @dataProvider pathProvider
     */
    public function testTrimsPathsAndAllowsSubfolders(
        string $path,
        string $expected,
    ): void {
        $builder = new Builder(__DIR__ . '/view-files', $path);
        $this->assertSame($expected, $builder->build());
    }
}
