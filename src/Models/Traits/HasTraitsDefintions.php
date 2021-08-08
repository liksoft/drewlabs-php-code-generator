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

trait HasTraitsDefintions
{
    use HasImportDeclarations;
    use OOPStructComponent;

    /**
     * List of traits.
     *
     * @var string[]
     */
    private $traits_;

    public function addTrait(string $trait)
    {
        $this->traits_[] = $trait;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getTraits(): ?array
    {
        return $this->traits_ ?? null;
    }
}
