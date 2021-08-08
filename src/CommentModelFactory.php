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

namespace Drewlabs\CodeGenerator;

use Drewlabs\CodeGenerator\Contracts\ComponentFactoryInterface;
use Drewlabs\CodeGenerator\Models\MultiLinePHPComment;
use Drewlabs\CodeGenerator\Models\SingleLinePHPComment;

class CommentModelFactory implements ComponentFactoryInterface
{
    /**
     * @var bool
     */
    private $multiline_;

    public function __construct(bool $multiline = true)
    {
        $this->multiline_ = $multiline;
    }

    public function make(...$args)
    {
        if ($this->multiline_) {
            return new MultiLinePHPComment(...$args);
        }

        return new SingleLinePHPComment(...$args);
    }
}
