<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Tests\Unit\HttpKernel\Controller\ArgumentResolver;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Stenope\Bundle\Content;
use Stenope\Bundle\ContentManagerInterface;
use Stenope\Bundle\HttpKernel\Controller\ArgumentResolver\ContentArgumentResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class ContentArgumentResolverTest extends TestCase
{
    use ProphecyTrait;

    private ContentArgumentResolver $resolver;

    /** @var ContentManagerInterface|ObjectProphecy */
    private ObjectProphecy $manager;

    protected function setUp(): void
    {
        $this->resolver = new ContentArgumentResolver(
            ($this->manager = $this->prophesize(ContentManagerInterface::class))->reveal()
        );
    }

    /**
     * @dataProvider provideSupportsData
     */
    public function testSupports(Request $request, ArgumentMetadata $argument, bool $expected): void
    {
        $this->manager->supports('App\Foo')->willReturn(true);
        $this->manager->supports('App\Bar')->willReturn(false);

        self::assertSame($expected, $this->resolver->supports($request, $argument));
    }

    public function provideSupportsData(): iterable
    {
        yield 'non-nullable, supported content class' => [
            new Request([], [], ['foo' => 'foo-1']),
            $argument = new ArgumentMetadata('foo', 'App\Foo', false, false, null, false),
            'expected' => true,
        ];

        yield 'non-nullable, non-supported content class' => [
            new Request([], [], ['bar' => 'bar-1']),
            $argument = new ArgumentMetadata('bar', 'App\Bar', false, false, null, false),
            'expected' => false,
        ];

        yield 'nullable, supported content class, but no matching request attribute (or null)' => [
            new Request(),
            $argument = new ArgumentMetadata('foo', 'App\Foo', false, false, null, true),
            'expected' => false,
        ];

        yield 'non-nullable, supported content class, and no matching request attribute (or null)' => [
            new Request(),
            $argument = new ArgumentMetadata('foo', 'App\Foo', false, false, null, false),
            'expected' => true,
        ];
    }

    public function testResolve(): void
    {
        $this->manager->getContent('App\Foo', 'foo-1')->willReturn($content = new Content('foo-1', 'App\Foo', 'Foo 1', 'markdown'));

        $request = new Request([], [], ['foo' => 'foo-1']);
        $argument = new ArgumentMetadata('foo', 'App\Foo', false, false, null, false);

        self::assertSame([$content], iterator_to_array($this->resolver->resolve($request, $argument)));
    }
}
