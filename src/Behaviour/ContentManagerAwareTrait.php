<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Behaviour;

use Stenope\Bundle\ContentManagerInterface;

/**
 * @see ContentManagerAwareInterface
 */
trait ContentManagerAwareTrait
{
    private ContentManagerInterface $contentManager;

    public function setContentManager(ContentManagerInterface $contentManager): void
    {
        $this->contentManager = $contentManager;
    }
}
