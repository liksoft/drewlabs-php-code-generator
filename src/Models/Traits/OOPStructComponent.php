<?php

declare(strict_types=1);

/*
 * This file is part of the Drewlabs package.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\CodeGenerator\Models\Traits;

use Drewlabs\CodeGenerator\Contracts\CallableInterface;
use Drewlabs\CodeGenerator\Helpers\Arr;

trait OOPStructComponent
{
    use BelongsToNamespace;
    use HasImportDeclarations;
    use HasPropertyDefinitions;
    use Type;

    /**
     * @var CallableInterface[]
     */
    private $methods_ = [];

    private $constructorMethodName_ = '__construct';

    public function addMethod(CallableInterface $method)
    {
        $methods = [];
        foreach (($this->methods_ ?? []) as $value) {
            $methods[$value->getName()] = $value;
        }
        sort($methods);
        $match = Arr::bsearch(array_keys($methods), $method, static function ($curr, CallableInterface $item) use ($methods) {
            if ($methods[$curr]->equals($item)) {
                return 0;
            }

            return strcmp($methods[$curr]->getName(), $item->getName()) > 0 ? -1 : 1;
        });
        if (-1 !== $match) {
            throw new \RuntimeException('Duplicated method definition : '.$method->getName());
        }
        if ($method->getName() === $this->constructorMethodName_) {
            $this->methods_ = [$method, ...($this->methods_ ?? [])];
        } else {
            $this->methods_[] = $method;
        }

        return $this;
    }

    /**
     * Returns the list of methods of the current component.
     *
     * @return CallableInterface[]
     */
    public function getMethods(): array
    {
        return $this->methods_ ?? [];
    }
}
