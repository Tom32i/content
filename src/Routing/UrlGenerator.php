<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Routing;

use Content\Builder\PageList;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * A wrapper for UrlGenerator that register every generated url in the PageList.
 */
class UrlGenerator implements UrlGeneratorInterface
{
    private UrlGeneratorInterface $urlGenerator;
    private PageList $pageList;

    public function __construct(UrlGeneratorInterface $urlGenerator, PageList $pageList)
    {
        $this->urlGenerator = $urlGenerator;
        $this->pageList = $pageList;
    }

    public function generate($name, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        $this->pageList->add(
            $this->urlGenerator->generate($name, $parameters, UrlGeneratorInterface::ABSOLUTE_URL)
        );

        return $this->urlGenerator->generate($name, $parameters, $referenceType);
    }

    public function setContext(RequestContext $context): void
    {
        $this->urlGenerator->setContext($context);
    }

    public function getContext()
    {
        return $this->urlGenerator->getContext();
    }
}
