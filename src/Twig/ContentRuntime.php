<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Twig;

use Stenope\Bundle\ContentManagerInterface;
use Twig\Extension\RuntimeExtensionInterface;

class ContentRuntime implements RuntimeExtensionInterface
{
    private ContentManagerInterface $contentManager;

    public function __construct(ContentManagerInterface $contentManager)
    {
        $this->contentManager = $contentManager;
    }

    public function getContent(string $type, string $slug): object
    {
        return $this->contentManager->getContent($type, $slug);
    }

    /**
     * @return object[]
     */
    public function listContents(string $type, $sortBy = null, $filterBy = null): array
    {
        return $this->contentManager->getContents($type, $sortBy, $filterBy);
    }
}
