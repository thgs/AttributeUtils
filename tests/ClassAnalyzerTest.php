<?php

declare(strict_types=1);

namespace Crell\AttributeUtils;

use Crell\AttributeUtils\Attributes\BasicClass;
use Crell\AttributeUtils\Attributes\BasicProperty;
use Crell\AttributeUtils\Attributes\ClassMethodsProperties;
use Crell\AttributeUtils\Attributes\ClassWithProperties;
use Crell\AttributeUtils\Attributes\ClassWithPropertiesWithSubAttributes;
use Crell\AttributeUtils\Attributes\ClassWithReflection;
use Crell\AttributeUtils\Attributes\GenericClass;
use Crell\AttributeUtils\Attributes\InheritableClassAttributeMain;
use Crell\AttributeUtils\Records\AttributesInheritChild;
use Crell\AttributeUtils\Records\ClassWithMethodsAndProperties;
use Crell\AttributeUtils\Records\ClassWithCustomizedFields;
use Crell\AttributeUtils\Records\ClassWithCustomizedPropertiesExcludeByDefault;
use Crell\AttributeUtils\Records\ClassWithDefaultFields;
use Crell\AttributeUtils\Records\ClassWithPropertiesWithReflection;
use Crell\AttributeUtils\Records\ClassWithSubAttributes;
use Crell\AttributeUtils\Records\NoProps;
use Crell\AttributeUtils\Records\NoPropsOverride;
use Crell\AttributeUtils\Records\Point;
use Crell\AttributeUtils\Records\PropertiesWithMultipleSubattributes;
use Crell\AttributeUtils\Records\TransitiveFieldClass;
use PHPUnit\Framework\TestCase;

class ClassAnalyzerTest extends TestCase
{

    /**
     * @test
     * @dataProvider attributeTestProvider()
     */
    public function analyze_classes(string $subject, string $attribute, callable $test): void
    {
        $analyzer = new Analyzer();

        $classDef = $analyzer->analyze($subject, $attribute);

        $test($classDef);
    }

    /**
     * @test
     * @dataProvider attributeObjectTestProvider()
     */
    public function analyze_objects(object $subject, string $attribute, callable $test): void
    {
        $analyzer = new Analyzer();

        $classDef = $analyzer->analyze($subject, $attribute);

        $test($classDef);
    }

    /**
     * @test
     */
    public function analyze_anonymous_objects(): void
    {
        $subject = new class {
            #[BasicProperty]
            public string $foo;
        };

        $analyzer = new Analyzer();

        $classDef = $analyzer->analyze($subject, ClassWithProperties::class);

        static::assertEquals('a', $classDef->properties['foo']->a);
        static::assertEquals('b', $classDef->properties['foo']->b);
    }

    /**
     * @see analyze_classes()
     */
    public function attributeTestProvider(): iterable
    {
        yield 'Generic' => [
            'subject' => Point::class,
            'attribute' => BasicClass::class,
            'test' => static function(BasicClass $classDef) {
                static::assertEquals(0, $classDef->a);
                static::assertEquals(0, $classDef->b);
            },
        ];

        yield 'Reflectable with no override value' => [
            'subject' => NoProps::class,
            'attribute' => ClassWithReflection::class,
            'test' => static function(ClassWithReflection $classDef) {
                static::assertEquals(1, $classDef->a);
                static::assertEquals(0, $classDef->b);
                static::assertEquals('NoProps', $classDef->name);
            },
        ];

        yield 'Reflectable with an override value' => [
            'subject' => NoPropsOverride::class,
            'attribute' => ClassWithReflection::class,
            'test' => static function(ClassWithReflection $classDef) {
                static::assertEquals(1, $classDef->a);
                static::assertEquals(0, $classDef->b);
                static::assertEquals('Overridden', $classDef->name);
            },
        ];

        yield 'Fieldable with default properties' => [
            'subject' => ClassWithDefaultFields::class,
            'attribute' => ClassWithProperties::class,
            'test' => static function(ClassWithProperties $classDef) {
                static::assertEquals('a', $classDef->properties['i']->a);
                static::assertEquals('b', $classDef->properties['i']->b);
                static::assertEquals('a', $classDef->properties['s']->a);
                static::assertEquals('b', $classDef->properties['s']->b);
                static::assertEquals('a', $classDef->properties['f']->a);
                static::assertEquals('b', $classDef->properties['f']->b);
            },
        ];

        yield 'Fieldable with customized properties' => [
            'subject' => ClassWithCustomizedFields::class,
            'attribute' => ClassWithProperties::class,
            'test' => static function(ClassWithProperties $classDef) {
                static::assertEquals('a', $classDef->properties['i']->a);
                static::assertEquals('b', $classDef->properties['i']->b);
                static::assertEquals('A', $classDef->properties['s']->a);
                static::assertEquals('b', $classDef->properties['s']->b);
                static::assertEquals('a', $classDef->properties['f']->a);
                static::assertEquals('B', $classDef->properties['f']->b);
            },
        ];

        yield 'Fieldable default no-include fields' => [
            'subject' => ClassWithCustomizedPropertiesExcludeByDefault::class,
            'attribute' => ClassWithProperties::class,
            'test' => static function(ClassWithProperties $classDef) {
                static::assertArrayNotHasKey('i', $classDef->properties);
                static::assertEquals('A', $classDef->properties['s']->a);
                static::assertEquals('b', $classDef->properties['s']->b);
                static::assertEquals('a', $classDef->properties['f']->a);
                static::assertEquals('B', $classDef->properties['f']->b);
            },
        ];

        yield 'Fieldable reflectable fields' => [
            'subject' => ClassWithPropertiesWithReflection::class,
            'attribute' => GenericClass::class,
            'test' => static function(GenericClass $classDef) {
                static::assertEquals('i', $classDef->properties['i']->name);
                static::assertEquals('beep', $classDef->properties['s']->name);
                static::assertEquals('f', $classDef->properties['f']->name);
            },
        ];

        yield 'Fieldable with sub-attributes' => [
            'subject' => ClassWithSubAttributes::class,
            'attribute' => ClassWithPropertiesWithSubAttributes::class,
            'test' => static function(ClassWithPropertiesWithSubAttributes $classDef) {
                static::assertEquals('C', $classDef->c);
                static::assertEquals('A', $classDef->properties['hasSub']->a);
                static::assertEquals('B', $classDef->properties['hasSub']->b);
                static::assertEquals('A', $classDef->properties['noSub']->a);
                static::assertEquals('B', $classDef->properties['noSub']->b);
            },
        ];

        yield 'Attributes and sub-attributes inherit' => [
            'subject' => AttributesInheritChild::class,
            'attribute' => InheritableClassAttributeMain::class,
            'test' => static function(InheritableClassAttributeMain $classDef) {
                static::assertEquals(2, $classDef->a);
                static::assertEquals('baz', $classDef->sub->foo);
                static::assertEquals(4, $classDef->properties['test']->a);
                static::assertEquals(1, $classDef->properties['added']->a);
            },
        ];

        yield 'Transitive fields inherit from the target class' => [
            'subject' => TransitiveFieldClass::class,
            'attribute' => GenericClass::class,
            'test' => static function(GenericClass $classDef) {
                static::assertEquals('Task', $classDef->properties['task']->beep);
                static::assertEquals('SmallTask', $classDef->properties['small']->beep);
                static::assertEquals('Task', $classDef->properties['big']->beep);
                static::assertEquals('biggie', $classDef->properties['big']->sub->title);
                static::assertEquals('smallie', $classDef->properties['small']->sub->title);
                static::assertNull($classDef->properties['task']->sub);
            },
        ];

        yield 'Property with multiple matching subattributes' => [
            'subject' => PropertiesWithMultipleSubattributes::class,
            'attribute' => GenericClass::class,
            'test' => static function(GenericClass $classDef) {
                static::assertEquals('Main', $classDef->properties['name']->name);
                static::assertEquals('first', $classDef->properties['name']->subs[0]->name);
                static::assertEquals('second', $classDef->properties['name']->subs[1]->name);
            },
        ];

        yield 'Class with methods and properties' => [
            'subject' => ClassWithMethodsAndProperties::class,
            'attribute' => ClassMethodsProperties::class,
            'test' => static function(ClassMethodsProperties $classDef) {
                static::assertCount(3, $classDef->properties);

                static::assertEquals('z', $classDef->methods['methodOne']->a);
                static::assertEquals('y', $classDef->methods['methodOne']->b);
                static::assertEquals('beep', $classDef->methods['methodOne']->name);
                static::assertEquals('a', $classDef->methods['methodTwo']->a);
                static::assertEquals('b', $classDef->methods['methodTwo']->b);
                static::assertEquals('methodTwo', $classDef->methods['methodTwo']->name);
                static::assertEquals('__construct', $classDef->methods['__construct']->name);

                static::assertCount(2, $classDef->methods['methodOne']->parameters);
                static::assertEquals(1, $classDef->methods['methodOne']->parameters['one']->x);
                static::assertEquals('one', $classDef->methods['methodOne']->parameters['one']->name);
                static::assertEquals(3, $classDef->methods['methodOne']->parameters['two']->x);
                static::assertEquals('beep', $classDef->methods['methodOne']->parameters['two']->name);

                static::assertCount(2, $classDef->methods['methodTwo']->parameters);
                static::assertEquals(5, $classDef->methods['methodTwo']->parameters['three']->x);
                static::assertEquals('three', $classDef->methods['methodTwo']->parameters['three']->name);
                static::assertEquals(1, $classDef->methods['methodTwo']->parameters['four']->x);
                static::assertEquals('four', $classDef->methods['methodTwo']->parameters['four']->name);
            },
        ];
    }

    /**
     * @see analyze_objects()
     */
    public function attributeObjectTestProvider(): iterable
    {
        $tests = iterator_to_array($this->attributeTestProvider());

        $new = [];
        foreach ($tests as $name => $test) {
            $test['subject'] = new $test['subject'];
            $new[$name . ' (Object)'] = $test;
        }
        return $new;
    }
}
