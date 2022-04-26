<?php

use Crell\AttributeUtils\Analyzer;
use Crell\AttributeUtils\HasSubAttributes;
use Crell\AttributeUtils\Multivalue;

require 'vendor/autoload.php';

#[\Attribute(Attribute::TARGET_CLASS)]
class MyParentAttrib implements HasSubAttributes, IteratorAggregate, ArrayAccess
{
    private array $children;

    /**
     * @return array<string, string>
     *   A mapping of attribute class name to the callback method that should be called with it.
     */
    public function subAttributes(): array
    {
        return [MyChildAttrib::class => 'fromChildren'];
    }

    public function fromChildren(array $children): void
    {
        $this->children = $children;
    }

    public function getIterator(): \ArrayIterator
    {
        return new ArrayIterator($this->children);
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->children);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->names[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new InvalidArgumentException();
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new InvalidArgumentException();
    }
}

#[\Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class MyChildAttrib extends MyParentAttrib implements Multivalue
{

}


#[MyChildAttrib]
#[MyChildAttrib]
class A
{

}

// $reflection = new ReflectionClass(A::class);
// var_dump($reflection->getAttributes(MyParentAttrib::class));

$analyzer = new Analyzer();
$result = $analyzer->analyze(A::class, MyParentAttrib::class);

var_dump($result);