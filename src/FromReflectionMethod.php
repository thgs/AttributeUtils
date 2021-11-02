<?php

declare(strict_types=1);

namespace Crell\AttributeUtils;

/**
 * Marks a method-targeting attribute as wanting reflection information.
 *
 * If a method-targeting attribute implements this interface, then after it
 * is instantiated the reflection object for the class will be passed to this
 * method.  The attribute may then extract whatever information it desires
 * and save it to object however it likes.
 *
 * Note that the attribute MUST NOT save the reflection object itself. That
 * would make the attribute object unserializable, and thus uncacheable.
 */
interface FromReflectionMethod
{
    public function fromReflection(\ReflectionMethod $subject): void;
}
