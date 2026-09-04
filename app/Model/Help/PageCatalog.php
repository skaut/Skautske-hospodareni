<?php

declare(strict_types=1);

namespace App\Model\Help;

use DirectoryIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use SplFileInfo;

use function class_exists;
use function dirname;
use function is_dir;
use function ksort;
use function lcfirst;
use function preg_match;
use function Safe\file_get_contents;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function substr;

/**
 * Lists the pages that can have contextual help, so the administration offers real
 * `Presenter:action` keys instead of a free-text field where a typo silently means
 * the help never shows up.
 *
 * Presenters are found by scanning the source tree rather than by
 * `get_declared_classes()`, because the class map autoloader only loads a presenter
 * once it is actually used.
 */
class PageCatalog
{
    private const PRESENTER_DIRECTORIES = ['/Presentation', '/presenters'];

    /**
     * Presenters that exist only as plumbing and never show contextual help.
     * Shared with page view counting, so a page missing from this list cannot
     * turn up in the usage figures either.
     */
    public const EXCLUDED_PRESENTERS = ['Error', 'Error4xx', 'Auth', 'SessionKeepAlive'];

    /** @var array<string, string>|null */
    private ?array $pages = null;

    public function __construct(private string $appDir)
    {
    }

    /**
     * Page keys in the form `Presenter:action`, ordered alphabetically.
     *
     * @return array<string, string>
     */
    public function getPages(): array
    {
        if ($this->pages !== null) {
            return $this->pages;
        }

        $pages = [];

        foreach ($this->findPresenterClasses() as $class) {
            $presenterName = $this->resolvePresenterName($class);

            if ($presenterName === null) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract()) {
                continue;
            }

            $actions = $this->resolveActions($reflection);

            foreach ($this->resolveActionsFromTemplates($class) as $action) {
                $actions[$action] = true;
            }

            foreach ($actions as $action => $_) {
                $key = $presenterName.':'.$action;
                $pages[$key] = $key;
            }
        }

        ksort($pages);
        $this->pages = $pages;

        return $pages;
    }

    public function has(string $pageKey): bool
    {
        return isset($this->getPages()[$pageKey]);
    }

    /** @return string[] */
    private function findPresenterClasses(): array
    {
        $classes = [];

        foreach (self::PRESENTER_DIRECTORIES as $directory) {
            $path = $this->appDir.$directory;

            if (! is_dir($path)) {
                continue;
            }

            /** @var SplFileInfo $file */
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file) {
                if (! $file->isFile() || ! str_ends_with($file->getFilename(), 'Presenter.php')) {
                    continue;
                }

                $source = file_get_contents($file->getPathname());

                if (preg_match('~^namespace\s+([^;]+);~m', $source, $namespace) !== 1) {
                    continue;
                }

                $class = $namespace[1].'\\'.substr($file->getFilename(), 0, -4);

                if (! class_exists($class)) {
                    continue;
                }

                $classes[] = $class;
            }
        }

        return $classes;
    }

    private function resolvePresenterName(string $class): ?string
    {
        if (preg_match('~^App\\\\Presentation\\\\([A-Za-z]+)\\\\([A-Za-z]+)\\\\[A-Za-z]+Presenter$~', $class, $m) === 1) {
            $name = $m[1].':'.$m[2];
        } elseif (preg_match('~^App\\\\([A-Za-z]+)Presenter$~', $class, $m) === 1) {
            $name = $m[1];
        } else {
            return null;
        }

        foreach (self::EXCLUDED_PRESENTERS as $excluded) {
            if ($name === $excluded || str_starts_with($name, $excluded.':')) {
                return null;
            }
        }

        return $name;
    }

    /**
     * @param ReflectionClass<object> $reflection
     *
     * @return array<string, true>
     */
    private function resolveActions(ReflectionClass $reflection): array
    {
        $actions = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();

            foreach (['action', 'render'] as $prefix) {
                if (! str_starts_with($name, $prefix) || $name === $prefix) {
                    continue;
                }

                $actions[lcfirst(substr($name, 6))] = true;
            }
        }

        return $actions;
    }

    /**
     * Actions that exist only as a template, without an `action`/`render` method.
     * A template is a page when it fills the layout's `content` block; partials such
     * as a grid definition do not.
     *
     * @return string[]
     */
    private function resolveActionsFromTemplates(string $class): array
    {
        $reflection = new ReflectionClass($class);
        $fileName = $reflection->getFileName();

        if ($fileName === false) {
            return [];
        }

        $actions = [];

        foreach (new DirectoryIterator(dirname($fileName)) as $file) {
            if ($file->isDot() || ! $file->isFile() || $file->getExtension() !== 'latte') {
                continue;
            }

            $name = $file->getBasename('.latte');

            if (str_starts_with($name, '@') || str_starts_with($name, '_') || str_starts_with($name, 'ex.')) {
                continue;
            }

            if (! str_contains(file_get_contents($file->getPathname()), '{block content}')) {
                continue;
            }

            $actions[] = $name;
        }

        return $actions;
    }
}
