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
use Drewlabs\Core\Helpers\Arrays\BinarySearchResult;

trait OOPStructComponent
{
    use HasImportDeclarations;
    use HasPropertyDefinitions;

    /**
     * @var string
     */
    private $name_;
    /**
     * @var CallableInterface[]
     */
    private $methods_ = [];

    /**
     * The namespace the class belongs to.
     *
     * @var string
     */
    private $namespace_;

    public function getName()
    {
        return $this->name_;
    }

    public function addMethod(CallableInterface $method)
    {
        $methods = [];
        foreach (($this->methods_ ?? []) as $value) {
            $methods[$value->getName()] = $value;
        }
        sort($methods);
        $match = drewlabs_core_array_bsearch(array_keys($methods), $method, static function ($curr, CallableInterface $item) use ($methods) {
            if ($methods[$curr]->equals($item)) {
                return BinarySearchResult::FOUND;
            }
            return strcmp($methods[$curr]->getName(), $item->getName()) > 0 ? BinarySearchResult::LEFT : BinarySearchResult::RIGHT;
        });
        if (BinarySearchResult::LEFT !== $match) {
            throw new \RuntimeException('Duplicated method definition : '.$method->getName());
        }
        $this->methods_[] = $method;

        return $this;
    }

    public function addToNamespace(string $namespace)
    {
        $this->namespace_ = $namespace;

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

    /**
     * Returns the namespace that the current class belongs to.
     */
    public function getNamespace(): ?string
    {
        return $this->namespace_ ?? null;
    }
}
