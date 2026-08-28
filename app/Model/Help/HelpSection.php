<?php

declare(strict_types=1);

namespace App\Model\Help;

use InvalidArgumentException;

use function array_filter;
use function array_map;
use function array_values;
use function trim;

/**
 * One block of contextual page help: a short bold heading, the text below it and
 * an optional bullet list (used for things like the e-mail placeholder reference).
 *
 * Help is stored as a list of these instead of free HTML so that every panel in the
 * application renders identically and an editor cannot break the layout.
 */
final class HelpSection
{
    public const HEADING_MAX_LENGTH = 60;
    public const TEXT_MAX_LENGTH = 500;

    /** @param string[] $items */
    private function __construct(private string $heading, private string $text, private array $items)
    {
    }

    /** @param string[] $items */
    public static function create(string $heading, string $text, array $items = []): self
    {
        $heading = trim($heading);
        $text = trim($text);
        $items = array_values(array_filter(
            array_map(static fn (string $item): string => trim($item), $items),
            static fn (string $item): bool => $item !== '',
        ));

        if ($heading === '') {
            throw new InvalidArgumentException('Help section heading must not be empty.');
        }

        if ($text === '') {
            throw new InvalidArgumentException('Help section text must not be empty.');
        }

        return new self($heading, $text, $items);
    }

    public function getHeading(): string
    {
        return $this->heading;
    }

    public function getText(): string
    {
        return $this->text;
    }

    /** @return string[] */
    public function getItems(): array
    {
        return $this->items;
    }

    public function hasItems(): bool
    {
        return $this->items !== [];
    }

    /** @return array{heading: string, text: string, items: string[]} */
    public function toArray(): array
    {
        return ['heading' => $this->heading, 'text' => $this->text, 'items' => $this->items];
    }

    /** @param array{heading: string, text: string, items?: string[]} $data */
    public static function fromArray(array $data): self
    {
        return self::create($data['heading'], $data['text'], $data['items'] ?? []);
    }
}
