<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle;

use Stenope\Bundle\ReverseContent\Context;
use Symfony\Component\ExpressionLanguage\Expression;

interface ContentManagerInterface
{
    /**
     * List all content for the given type
     *
     * @template T
     *
     * @param class-string<T>                  $type     Model FQCN e.g. "App/Model/Article"
     * @param string|array|callable            $sortBy   String, array or callable
     * @param string|array|callable|Expression $filterBy Array, callable or an {@link Expression} instance / string
     *                                                   to filter out with an expression using the ExpressionLanguage
     *                                                   component.
     *
     * @return array<string,T> List of decoded contents with their slug as key
     */
    public function getContents(string $type, $sortBy = null, $filterBy = null): array;

    /**
     * Fetch a specific content
     *
     * @template T
     *
     * @param class-string<T> $type Model FQCN e.g. "App/Model/Article"
     * @param string          $id   Unique identifier (slug)
     *
     * @return T An object of the given type.
     */
    public function getContent(string $type, string $id): object;

    /**
     * Attempt to reverse resolve a content according to a context.
     * E.g: attempt to resolve a content relative to another one through its filesystem path.
     */
    public function reverseContent(Context $context): ?Content;

    public function supports(string $type): bool;
}
