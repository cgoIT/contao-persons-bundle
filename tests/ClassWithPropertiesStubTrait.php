<?php

declare(strict_types=1);

/*
 * This file is part of cgoit\contao-persons-bundle for Contao Open Source CMS.
 *
 * @copyright  Copyright (c) cgoIT
 * @author     cgoIT <https://cgo-it.de>
 * @license    LGPL-3.0-or-later
 */

namespace Cgoit\PersonsBundle\Tests;

use PHPUnit\Framework\MockObject\Stub;

/**
 * Replaces the helper of contao/test-case, which cannot be installed together with
 * PHPUnit 10+ for Contao < 5.6. Creates a stub whose magic properties are served
 * from the given array.
 */
trait ClassWithPropertiesStubTrait
{
    /**
     * @param class-string         $class
     * @param array<string, mixed> $properties
     */
    protected function createClassWithPropertiesStub(string $class, array $properties = []): Stub
    {
        $stub = $this->createStub($class);

        $stub->method('__get')->willReturnCallback(static function (string $key) use (&$properties) {
            return $properties[$key] ?? null;
        });

        if (method_exists($class, '__set')) {
            $stub->method('__set')->willReturnCallback(static function (string $key, mixed $value) use (&$properties): void {
                $properties[$key] = $value;
            });
        }

        if (method_exists($class, '__isset')) {
            $stub->method('__isset')->willReturnCallback(static function (string $key) use (&$properties) {
                return isset($properties[$key]);
            });
        }

        return $stub;
    }
}
