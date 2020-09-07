<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content;

use Content\Builder\PageList;
use Content\Builder\RouteInfo;
use Content\Builder\Sitemap;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

/**
 * Static route builder
 */
class Builder
{
    private RouterInterface $router;
    private HttpKernelInterface $httpKernel;
    private Environment $templating;
    private PageList $pageList;
    private Sitemap $sitemap;

    /** Path to output the static site */
    private string $buildDir;
    private FileSystem $files;

    /** Files to copy after build */
    private array $filesToCopy;
    private LoggerInterface $logger;

    public function __construct(
        RouterInterface $router,
        HttpKernelInterface $httpKernel,
        Environment $templating,
        PageList $pageList,
        Sitemap $sitemap,
        string $buildDir,
        array $filesToCopy = [],
        ?LoggerInterface $logger = null
    ) {
        $this->router = $router;
        $this->httpKernel = $httpKernel;
        $this->templating = $templating;
        $this->pageList = $pageList;
        $this->sitemap = $sitemap;
        $this->buildDir = $buildDir;
        $this->filesToCopy = $filesToCopy;
        $this->files = new Filesystem();
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Build static site
     */
    public function build(bool $sitemap = true, bool $expose = true): void
    {
        $this->clear();

        $this->scanAllRoutes();

        if ($expose) {
            $this->copyFiles();
        }

        $this->buildPages();

        if ($sitemap) {
            $this->buildSitemap();
        }
    }

    public function setBuildDir(string $buildDir): void
    {
        $this->buildDir = $buildDir;
    }

    public function getBuildDir(): string
    {
        return $this->buildDir;
    }

    /**
     * Set host name
     */
    public function setHost(string $host): void
    {
        $this->router->getContext()->setHost($host);
    }

    /**
     * Set HTTP Scheme
     */
    public function setScheme(string $scheme): void
    {
        $this->router->getContext()->setScheme($scheme);
    }

    /**
     * Clear destination folder
     */
    private function clear(): void
    {
        if ($this->files->exists($this->buildDir)) {
            $this->files->remove($this->buildDir);
        }

        $this->files->mkdir($this->buildDir);
    }

    /**
     * Scan all declared route and tries to add them to the page list.
     */
    private function scanAllRoutes(): void
    {
        $routes = RouteInfo::createFromRouteCollection($this->router->getRouteCollection());

        foreach ($routes as $name => $route) {
            if ($route->isVisible() && $route->isGettable()) {
                try {
                    $url = $this->router->generate($name, [], UrlGeneratorInterface::ABSOLUTE_URL);
                } catch (\Exception $exception) {
                    continue;
                }

                $this->pageList->add($url);
            }
        }
    }

    /**
     * Build all pages
     */
    private function buildPages(): void
    {
        while ($url = $this->pageList->getNext()) {
            $this->buildUrl($url);
            $this->pageList->markAsDone($url);
        }
    }

    /**
     * Build xml sitemap file
     */
    private function buildSitemap(): void
    {
        $content = $this->templating->render('@Content/sitemap.xml.twig', ['sitemap' => $this->sitemap]);

        $this->write($content, '/', 'sitemap.xml');
    }

    private function copyFiles(): void
    {
        foreach ($this->filesToCopy as [
            'src' => $src,
            'dest' => $dest,
            'fail_if_missing' => $failIfMissing,
            'excludes' => $excludes,
        ]) {
            $dest ??= basename($src);

            if (is_dir($src)) {
                if (\count($excludes) > 0) {
                    $iterator = (new Finder())->in($src);
                    foreach ($excludes as $exclude) {
                        $iterator->notName($exclude)->files();
                    }
                }

                $this->files->mirror($src, "$this->buildDir/$dest", $iterator ?? null);
                continue;
            }

            if (!is_file($src)) {
                if ($failIfMissing) {
                    throw new \RuntimeException(sprintf(
                        'Failed to copy "%s" because the path is neither a file or a directory.',
                        $src
                    ));
                }

                $this->logger->warning('Failed to copy "{src}" because the path is neither a file or a directory.', [
                    'src' => $src,
                    'dest' => $dest,
                ]);

                continue;
            }

            $this->files->copy($src, "$this->buildDir/$dest");
        }
    }

    /**
     * Build the given Route into a file
     */
    private function buildUrl(string $url): void
    {
        $request = Request::create($url, 'GET');

        try {
            $response = $this->httpKernel->handle($request, HttpKernelInterface::MASTER_REQUEST, false);
        } catch (\Throwable $exception) {
            throw new \Exception(sprintf('Could not build url "%s".', $url), 0, $exception);
        }

        $this->httpKernel->terminate($request, $response);

        list($path, $file) = $this->getFilePath($request->getPathInfo());

        $this->write($response->getContent(), $path, $file);
    }

    /**
     * Get file path from URL
     */
    private function getFilePath(string $url): array
    {
        $info = pathinfo($url);

        if (!isset($info['extension'])) {
            return [$url, 'index.html'];
        }

        return [$info['dirname'], $info['basename']];
    }

    /**
     * Write a file
     *
     * @param string $content The file content
     * @param string $path    The directory to put the file in (in the current destination)
     * @param string $file    The file name
     */
    private function write(string $content, string $path, string $file): void
    {
        $directory = sprintf('%s/%s', $this->buildDir, trim($path, '/'));

        if (!$this->files->exists($directory)) {
            $this->files->mkdir($directory);
        }

        $this->files->dumpFile(sprintf('%s/%s', $directory, $file), $content);
    }
}
