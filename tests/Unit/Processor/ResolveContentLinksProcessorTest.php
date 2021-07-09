<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Tests\Unit\Processor;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Stenope\Bundle\Content;
use Stenope\Bundle\ContentManagerInterface;
use Stenope\Bundle\Processor\ResolveContentLinksProcessor;
use Stenope\Bundle\ReverseContent\RelativeLinkContext;
use Stenope\Bundle\Routing\ContentUrlResolver;
use Stenope\Bundle\Service\NaiveHtmlCrawlerManager;

class ResolveContentLinksProcessorTest extends TestCase
{
    use ProphecyTrait;

    public function testResolveLinks(): void
    {
        $data = [
            'content' => <<<HTML
            <a href="/absolute-link">Don't change this</a>
            <a href="http://external.com">Don't change this</a>
            <a href="//external.com">Don't change this</a>
            <a href="#anchor">Don't change this</a>

            <a href="../other-contents/another-content.md">Another content</a>
            <a href="../other-contents/another-content.md#some-anchor">Another content with anchor</a>
            HTML,
        ];

        $currentContent = new Content('some-content', 'SomeContent', 'rawContent', 'markdown', null, null, [
            'path' => '/workspace/project/content/current.md',
            'provider' => 'files',
        ]);

        $urlGenerator = $this->prophesize(ContentUrlResolver::class);

        $processor = new ResolveContentLinksProcessor($urlGenerator->reveal(), new NaiveHtmlCrawlerManager());

        $manager = $this->prophesize(ContentManagerInterface::class);
        $processor->setContentManager($manager->reveal());

        $manager->reverseContent(new RelativeLinkContext(
            $currentContent->getMetadata(),
            '../other-contents/another-content.md',
        ))->shouldBeCalledTimes(2)->willReturn(
            $resolvedContent = new Content('some-content', 'AnotherContent', 'rawContent', 'markdown', null, null, [
                'path' => '/workspace/project/other-contents/another-content.md',
            ])
        );

        $urlGenerator->resolveUrl($resolvedContent)->shouldBeCalledTimes(2)->willReturn('/other-contents-route-path/another-contents');

        $processor->__invoke($data, $currentContent);

        self::assertXmlStringEqualsXmlString(<<<HTML
            <body>
                <a href="/absolute-link">Don't change this</a>
                <a href="http://external.com">Don't change this</a>
                <a href="//external.com">Don't change this</a>
                <a href="#anchor">Don't change this</a>
                <a href="/other-contents-route-path/another-contents">Another content</a>
                <a href="/other-contents-route-path/another-contents#some-anchor">Another content with anchor</a>
            </body>
            HTML,
            $data['content']
        );
    }
}
