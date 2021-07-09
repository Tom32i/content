<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Behaviour;

use Stenope\Bundle\ContentManagerInterface;

interface ContentManagerAwareInterface
{
    /**
     * Sets the owning ContentManager object.
     */
    public function setContentManager(ContentManagerInterface $contentManager);
}
