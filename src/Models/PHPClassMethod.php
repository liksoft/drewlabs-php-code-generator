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

use Drewlabs\CodeGenerator\CommentModelFactory;
use Drewlabs\CodeGenerator\Contracts\CallableInterface;
use Drewlabs\CodeGenerator\Contracts\ClassMemberInterface;
use Drewlabs\CodeGenerator\Contracts\FunctionParameterInterface;
use Drewlabs\CodeGenerator\Helpers\PHPLanguageDefifinitions;
use Drewlabs\CodeGenerator\Models\Traits\BelongsToNamespace;
use Drewlabs\CodeGenerator\Models\Traits\HasImportDeclarations;
use Drewlabs\CodeGenerator\Models\Traits\HasIndentation;
use Drewlabs\CodeGenerator\Models\Traits\OOPStructComponentMembers;
use Drewlabs\Core\Helpers\Arrays\BinarySearchResult;

class PHPClassMethod implements CallableInterface, ClassMemberInterface
{
    use BelongsToNamespace;
    use HasImportDeclarations;
    use HasIndentation;
    use OOPStructComponentMembers;

    /**
     * @var string
     */
    private $name_;
    /**
     * @var FunctionParameterInterface[]
     */
    private $params_;

    /**
     * PHP Stringeable component.
     *
     * @var mixed
     */
    private $comment_;

    /**
     * The returns type of the function.
     *
     * @var string|array
     */
    private $returns_;

    /**
     * @var string
     */
    private $exceptions_;

    /**
     * Indicates whether the method is static or not.
     *
     * @var bool
     */
    private $isStatic_;

    /**
     * Method defintion content.
     *
     * @var string[]
     */
    private $contents_;

    /**
     * Indicates whether the definition is return as interface method or a class method.
     *
     * @var bool
     */
    private $isInterfaceMethod_ = false;

    public function __construct(
        string $name,
        array $params = [],
        ?string $returns = null,
        $modifier = 'public',
        $descriptors = ''
    ) {
        $this->name_ = $name;
        // Add list of params to the method
        foreach (array_filter($params ?? []) as $value) {
            $this->addParam($value);
        }
        if (null !== $descriptors) {
            $this->addComment($descriptors);
        }
        if (null !== $modifier) {
            $this->setModifier($modifier);
        }
        if ($returns) {
            $this->setReturnType($returns);
        }
    }

    public function __toString(): string
    {
        $this->setImports()->setComments();
        $indentation = $this->getIndentation();
        if (null !== $indentation) {
            $parts[] = $this->comment_->setIndentation($this->getIndentation())->__toString();
        } else {
            $parts[] = $this->comment_->__toString();
        }
        $accessModifier = (null !== $this->accessModifier_) && \in_array(
            $this->accessModifier_,
            [
                'private', 'protected', 'public',
            ],
            true
        ) && !$this->isInterfaceMethod_ ? $this->accessModifier_ : 'public';
        // Start the declaration
        $declaration = $this->isStatic_ ? "$accessModifier static function $this->name_(" : "$accessModifier function $this->name_(";
        // Add method params
        if (null !== $this->params_) {
            $params = array_map(static function ($param) {
                $type = null === $param->type() ? '' : $param->type();
                $result = "$type \$".$param->name();

                return null === $param->defaultValue() ? $result : "$result = ".drewlabs_core_strings_replace('"null"', 'null', $param->defaultValue());
            }, array_merge(
                array_filter($this->params_, static function ($p) {
                    return !$p->isOptional();
                }),
                array_filter($this->params_, static function ($p) {
                    return $p->isOptional();
                })
            ));
            $declaration .= implode(', ', $params);
        }
        // Add the closing parenthesis
        $declaration .= ')';
        // If it is an interface method, close the definition
        if ($this->isInterfaceMethod_) {
            $parts[] = "$declaration;";
        } else {
            // If it is not an interface method, add the method body
            $parts[] = $declaration;
            $parts[] = '{';
            if (!empty(($contents = array_merge(["\t# code..."], $this->contents_ ?? [])))) {
                $counter = 0;
                $parts[] = implode(\PHP_EOL, array_map(static function ($content) use ($indentation, &$counter) {
                    $content = $indentation && $counter > 0 ? $indentation.$content : $content;
                    ++$counter;

                    return $content;
                }, $contents));
            }
            $parts[] = '}';
        }
        if ($indentation) {
            $parts = array_map(static function ($part) use ($indentation) {
                return $indentation."$part";
            }, $parts);
        }

        return implode(\PHP_EOL, $parts);
    }

    public function throws($exceptions = [])
    {
        if (null !== $exceptions) {
            $exceptions = drewlabs_core_strings_is_str($exceptions) ? [$exceptions] : (\is_array($exceptions) ? $exceptions : []);
            foreach ($exceptions as $value) {
                if (drewlabs_core_strings_contains($value, '\\')) {
                    $this->imports_[] = $value;
                    $this->exceptions_[] = drewlabs_core_strings_after_last('\\', $value);
                } else {
                    $this->exceptions_[] = $value;
                }
            }
        }

        return $this;
    }

    /**
     * Add a new Parameter to the method.
     *
     * @return self
     */
    public function addParam(FunctionParameterInterface $param)
    {
        //region Validate method parameters for duplicated entries
        $params = [];
        foreach (($this->params_ ?? []) as $value) {
            $params[$value->name()] = $value;
        }
        sort($params);
        $match = drewlabs_core_array_bsearch(array_keys($params), $param, static function ($curr, FunctionParameterInterface $item) use ($params) {
            if ($params[$curr]->equals($item)) {
                return BinarySearchResult::FOUND;
            }

            return strcmp($params[$curr]->name(), $item->name()) > 0 ? BinarySearchResult::LEFT : BinarySearchResult::RIGHT;
        });
        if (BinarySearchResult::LEFT !== $match) {
            throw new \RuntimeException(sprintf('Duplicated entry %s in method %s definition : ', $param->name(), $this->name_));
        }
        //endregion Validate method parameters for duplicated entries
        $this->params_[] = $param;

        return $this;
    }

    public function asStatic(bool $value)
    {
        $this->isStatic_ = '__construct' === $this->name_ ? false : ($value || false);

        return $this;
    }

    public function addContents(string $contents)
    {
        $self = $this;
        if (null !== $contents) {
            $values = explode(\PHP_EOL, $contents);
            $values = array_map(static function ($content) {
                return drewlabs_core_strings_rtrim("$content", ';');
            }, $values);
            foreach ($values as $value) {
                // code...
                $self = $self->addLine($value);
            }
        }

        return $self;
    }

    /**
     * {@inheritDoc}
     *
     * Note : Lines must not be terminated with extras ; because the implementation will add a trailing ; at the end
     */
    public function addLine(string $line)
    {
        // Checks if the line is an expression, a block or a comments
        if (empty($line) || PHPLanguageDefifinitions::isComment($line) || PHPLanguageDefifinitions::isBlock($line)) {
            $this->contents_[] = "\t$line";
        } else {
            $this->contents_[] = "\t$line;";
        }

        return $this;
    }

    public function asInterfaceMethod()
    {
        $this->isInterfaceMethod_ = true;

        return $this;
    }

    public function equals(CallableInterface $value)
    {
        return $this->name_ === $value->getName();
        // If PHP Support method overloading go deep to method definitions
    }

    public function setReturnType(string $type)
    {
        $this->returns_ = $type;

        return $this;
    }

    protected function setImports()
    {
        if (null !== $this->returns_) {
            if (drewlabs_core_strings_contains($this->returns_, '\\')) {
                $this->returns_ = $this->addClassPathToImportsPropertyAfter(function ($classPath) {
                    return $this->getClassFromClassPath($classPath);
                })($this->returns_);
            }
        }
        $values = [];
        if ((null !== $this->params_)) {
            $values = \is_array($this->params_) ? $this->params_ : (\is_string($this->params_) ? [$this->params_] : []);
            $params = [];
            foreach ($values as $value) {
                if (drewlabs_core_strings_contains($value->type(), '\\')) {
                    $params[] = new PHPFunctionParameter(
                        $value->name(),
                        $this->addClassPathToImportsPropertyAfter(function ($classPath) {
                            return $this->getClassFromClassPath($classPath);
                        })($value->type()),
                        $value->defaultValue()
                    );
                } else {
                    $params[] = $value;
                }
            }
            $this->params_ = $params;
        }

        return $this;
    }

    protected function setComments()
    {
        $descriptors = array_filter((drewlabs_core_strings_is_str($this->descriptors_) ? [$this->descriptors_] : $this->descriptors_) ?? []);
        if (!empty($descriptors)) {
            // Add a line separator between the descriptors and other definitions
            $descriptors[] = '';
        }
        // Generates method params comments
        if (null !== $this->params_) {
            foreach ($this->params_ as $value) {
                $type = null === $value->type() ? 'mixed' : $value->type();
                $descriptors[] = '@param '.$type.' '.$value->name();
            }
        }
        // Generate exception comment
        if ((null !== $this->exceptions_) && \is_array($this->exceptions_)) {
            foreach ($this->exceptions_ as $value) {
                $descriptors[] = "@throws $value";
            }
        }
        // Generate returns comment
        if (null !== $this->returns_) {
            $descriptors[] = '@return '.$this->returns_;
        }
        $this->comment_ = (new CommentModelFactory(true))->make($descriptors);

        return $this;
    }
}
