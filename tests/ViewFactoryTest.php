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
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Laucov\Views\ViewFactory
 */
class ViewFactoryTest extends TestCase
{

    /**
     * @covers ::__construct
     * @covers ::getView
     * @uses Laucov\Views\Builder::__construct
     * @uses Laucov\Views\Builder::build
     * @uses Laucov\Views\View::__construct
     * @uses Laucov\Views\View::get
     */
    public function testCanCreateViews(): void
    {
        // Create view from factory.
        $factory = new ViewFactory(
            __DIR__ . '/view-files',
            __DIR__ . '/view-cache',
        );
        $view = $factory->getView('view-i');

        // Check properties.
        $reflection = new \ReflectionObject($view);
        $this->assertSame(
            __DIR__ . '/view-files',
            $reflection->getProperty('directory')->getValue($view),
        );
        $this->assertSame(
            __DIR__ . '/view-cache',
            $reflection->getProperty('cacheDirectory')->getValue($view),
        );
    }
}
