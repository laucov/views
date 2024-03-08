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

use Laucov\Views\ViewFactory;
use Laucov\Views\View;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Laucov\Views\ViewFactory
 */
class ViewFactoryTest extends TestCase
{
    public function pathProvider(): array
    {
        return [
            ['view-h', '<p>Hello, World!</p>'],
            ['view-i', '<p>Hello, Universe!</p>'],
            ['/view-i', '<p>Hello, Universe!</p>'],
            ['/view-i/', '<p>Hello, Universe!</p>'],
            ['view-i/', '<p>Hello, Universe!</p>'],
            ['subfolder/view-j', '<p>Hello, Earth!</p>'],
            ['/subfolder/view-j', '<p>Hello, Earth!</p>'],
            ['/subfolder/view-j/', '<p>Hello, Earth!</p>'],
            ['subfolder/view-j/', '<p>Hello, Earth!</p>'],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::getView
     * @uses Laucov\Views\Builder::__construct
     * @uses Laucov\Views\Builder::generate
     * @uses Laucov\Views\View::__construct
     * @uses Laucov\Views\View::get
     * @dataProvider pathProvider
     */
    public function testCanCreateViews(string $path, string $expected): void
    {
        $factory = new ViewFactory(
            __DIR__ . '/view-files',
            __DIR__ . '/view-cache',
        );
        $view = $factory->getView($path);
        $this->assertSame($expected, $view->get());
    }
}
