<?php

declare(strict_types=1);

namespace PHPColliderScope;

final readonly class CollisionConfig
{
    private const string DefaultExtension = 'php';

    /** @var list<string> lowercase extensions (no leading dot) treated as PHP source */
    public array $fileExtensions;

    /**
     * @param list<string> $additionalFileExtensions extra file extensions to scan
     *        alongside "php" (e.g. ["phpt", "phtml"]). Leading dots are stripped
     *        and extensions are matched case-insensitively.
     */
    public function __construct(
        public bool $findClassNamespaceCollision = false,
        public bool $findFunctionNamespaceCollision = false,
        array $additionalFileExtensions = [],
    ) {
        $this->fileExtensions = self::normalizeExtensions([
            self::DefaultExtension,
            ...$additionalFileExtensions,
        ]);
    }

    /** Finds both class-like and function collisions, scanning only ".php" files. */
    public static function default(): self
    {
        return new self(
            findClassNamespaceCollision: true,
            findFunctionNamespaceCollision: true,
        );
    }

    public function withFileExtension(string $extension): self
    {
        return new self(
            $this->findClassNamespaceCollision,
            $this->findFunctionNamespaceCollision,
            [...$this->fileExtensions, $extension],
        );
    }

    public function hasFileExtension(string $extension): bool
    {
        return in_array(strtolower(ltrim($extension, '.')), $this->fileExtensions, true);
    }

    /**
     * @param list<string> $extensions
     * @return list<string>
     */
    private static function normalizeExtensions(array $extensions): array
    {
        $normalized = array_map(
            static fn (string $extension): string => strtolower(ltrim($extension, '.')),
            $extensions,
        );

        return array_values(array_unique($normalized));
    }
}
