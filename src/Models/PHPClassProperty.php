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

namespace Drewlabs\CodeGenerator\Models;

use Drewlabs\CodeGenerator\Contracts\ClassMemberInterface;
use Drewlabs\CodeGenerator\Contracts\ValueContainer;
use Drewlabs\CodeGenerator\Helpers\Str;
use Drewlabs\CodeGenerator\Models\Traits\BelongsToNamespace;
use Drewlabs\CodeGenerator\Models\Traits\HasImportDeclarations;
use Drewlabs\CodeGenerator\Models\Traits\HasIndentation;
use Drewlabs\CodeGenerator\Models\Traits\OOPStructComponentMembers;
use Drewlabs\CodeGenerator\Models\Traits\Type;
use Drewlabs\CodeGenerator\Models\Traits\ValueContainer as TraitsValueContainer;
use Drewlabs\CodeGenerator\Types\PHPTypesModifiers;

class PHPClassProperty implements ValueContainer, ClassMemberInterface
{
    use BelongsToNamespace;
    use HasImportDeclarations;
    use HasIndentation;
    use OOPStructComponentMembers;
    use TraitsValueContainer;
    use Type;

    /**
     * Class instances initializer.
     *
     * @param string       $modifier
     * @param string|array $default
     */
    public function __construct(
        string $name,
        ?string $type = null,
        ?string $modifier = 'public',
        $default = null,
        $descriptors = ''
    ) {
        $this->setName($name);
        if (null !== $type) {
            $this->setType($type);
        }
        if (null !== $descriptors) {
            $this->addComment($descriptors);
        }
        if (null !== $modifier) {
            $this->setModifier($modifier);
        }
        $this->value($default);
    }

    public function __toString(): string
    {
        $this->prepare()
            ->setComments()
            ->value($this->value());
        $value = $this->parsePropertyValue();
        $name = $this->getName();
        // Generate comments
        if ($this->getIndentation()) {
            $parts[] = $this->comment_->setIndentation($this->getIndentation())->__toString();
        } else {
            $parts[] = $this->comment_->__toString();
        }
        // Generate defintion / declarations
        $modifier = (null !== $this->accessModifier()) && \in_array(
            $this->accessModifier(),
            ['private', 'protected', 'public'],
            true
        ) ? $this->accessModifier() : PHPTypesModifiers::PUBLIC;

        $definition = $this->isConstant_ ? sprintf('%s %s %s', $modifier, PHPTypesModifiers::CONSTANT, Str::upper($name)) : sprintf('%s $%s', $modifier, $name);
        if (Str::contains($value, "'[") && Str::contains($value, "]'")) {
            $value = str_replace(" ]'", ']', str_replace("'[", '[', $value));
        }
        $definition .= $value && \is_string($value) && !empty($value) ? str_replace('"null"', 'null', str_replace(["''"], "'", str_replace(['""'], '"', " = $value;"))) : ';';
        $parts[] = $definition;
        if ($this->getIndentation()) {
            $parts = array_map(function ($part) {
                return $this->getIndentation() . $part;
            }, $parts);
        }

        return implode(\PHP_EOL, $parts);
    }
}
