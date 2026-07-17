<?php

declare(strict_types=1);

namespace PHPColliderScope\PHPDocumentParser;

use FsKit\File;
use PHPColliderScope\PHPDocumentParser\Document\PhpDocument;
use PHPColliderScope\PHPDocumentParser\Document\Symbol;
use PHPColliderScope\PHPDocumentParser\Document\SymbolKind;
use PHPColliderScope\PHPDocumentParser\Tokenizers\Tokenizer;

final readonly class DeclarationExtractor
{
    public function __construct(
        private Tokenizer $tokenizer
    ) {
    }

    public function extract(File $file): PhpDocument
    {
        $tokens = $this->tokenizer->tokenize($file->read());
        $count = count($tokens);

        $namespace = '';
        $fileNamespace = null;

        $braceDepth = 0;
        $namespaceClosingDepth = null;
        $pendingNamespaceBlock = false;

        /** @var list<int> $classBodyDepths */
        $classBodyDepths = [];
        $pendingClassBody = false;

        /** @var list<Symbol> $declarations */
        $declarations = [];

        /** @var int|string|null $previousSignificantToken token id, or the raw string for single-char tokens */
        $previousSignificantToken = null;

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            if (is_string($token)) {
                $previousSignificantToken = $token;

                if ($token === '{') {
                    $braceDepth++;

                    if ($pendingClassBody) {
                        $classBodyDepths[] = $braceDepth;
                        $pendingClassBody = false;
                    }

                    if ($pendingNamespaceBlock) {
                        $namespaceClosingDepth = $braceDepth;
                        $pendingNamespaceBlock = false;
                    }
                } elseif ($token === '}') {
                    if ($classBodyDepths !== [] && end($classBodyDepths) === $braceDepth) {
                        array_pop($classBodyDepths);
                    }

                    if ($namespaceClosingDepth !== null && $braceDepth === $namespaceClosingDepth) {
                        $namespace = '';
                        $namespaceClosingDepth = null;
                    }

                    $braceDepth--;
                }

                continue;
            }

            [$id, $tokenText, $line] = $token;

            if ($id === T_WHITESPACE || $id === T_COMMENT || $id === T_DOC_COMMENT) {
                continue;
            }

            $previous = $previousSignificantToken;
            $previousSignificantToken = $id;

            // String interpolation openers ("{$x}" / "${x}") arrive as
            // array tokens while their closing '}' arrives as a raw
            // string, so count them here or brace depth goes unbalanced.
            if ($id === T_CURLY_OPEN || $id === T_DOLLAR_OPEN_CURLY_BRACES) {
                $braceDepth++;
                continue;
            }

            if ($id === T_NAMESPACE) {
                [$name, $nextIndex, $opensBlock] = $this->readNamespaceName($tokens, $i + 1);
                $namespace = $name;
                $fileNamespace ??= ($name !== '' ? $name : null);
                $pendingNamespaceBlock = $opensBlock;
                $i = $nextIndex;
                continue;
            }

            if ($id === T_CLASS || $id === T_INTERFACE || $id === T_TRAIT || $id === T_ENUM) {
                if ($previous === T_DOUBLE_COLON) {
                    continue; // `Foo::class` constant fetch, not a declaration
                }

                $name = $this->readNextName($tokens, $i + 1);

                if ($name !== null) {
                    $declarations[] = new Symbol(
                        $this->kindFor($id),
                        $name,
                        $namespace !== '' ? $namespace : null,
                        $file,
                        $line,
                    );
                }

                // Anonymous classes (`new class { ... }`) hit this branch
                // too since T_CLASS fires either way - $name is simply
                // null for them (readNextName sees '(' or '{' next, not a
                // T_STRING). We still need to track their body so methods
                // inside an anonymous class aren't mistaken for globals.
                $pendingClassBody = true;
                continue;
            }

            if ($id === T_FUNCTION) {
                if ($previous === T_USE) {
                    continue; // `use function foo;` import, not a declaration
                }

                if ($classBodyDepths !== []) {
                    continue; // it's a method, not a global function
                }

                $name = $this->readFunctionName($tokens, $i + 1);

                if ($name !== null) {
                    $declarations[] = new Symbol(
                        SymbolKind::FunctionType,
                        $name,
                        $namespace !== '' ? $namespace : null,
                        $file,
                        $line,
                    );
                }
            }
        }

        return new PhpDocument($file, $fileNamespace, $declarations);
    }

    private function kindFor(int $tokenId): SymbolKind
    {
        return match ($tokenId) {
            T_CLASS => SymbolKind::ClassType,
            T_INTERFACE => SymbolKind::InterfaceType,
            T_TRAIT => SymbolKind::TraitType,
            T_ENUM => SymbolKind::EnumType,
        };
    }

    private function readNextName(array $tokens, int $startIndex): ?string
    {
        $count = count($tokens);

        for ($i = $startIndex; $i < $count; $i++) {
            $token = $tokens[$i];

            if (is_string($token)) {
                return null;
            }

            [$id, $text] = $token;

            if ($id === T_WHITESPACE || $id === T_COMMENT || $id === T_DOC_COMMENT) {
                continue;
            }

            return $id === T_STRING ? $text : null;
        }

        return null;
    }

    private function readFunctionName(array $tokens, int $startIndex): ?string
    {
        $count = count($tokens);

        for ($i = $startIndex; $i < $count; $i++) {
            $token = $tokens[$i];

            if (is_string($token)) {
                if ($token === '&') {
                    continue;
                }

                return null;
            }

            [$id, $text] = $token;

            if ($id === T_WHITESPACE || $id === T_COMMENT || $id === T_DOC_COMMENT) {
                continue;
            }

            // Since PHP 8.1 the by-ref '&' is an array token, not a raw
            // string, so `function &foo()` lands here rather than above.
            if ($id === T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG
                || $id === T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG
            ) {
                continue;
            }

            return $id === T_STRING ? $text : null;
        }

        return null;
    }

    /**
     * Reads a (possibly qualified) namespace name after the `namespace`
     * keyword, stopping at `;` (statement form) or `{` (block form).
     *
     * @return array{0:string,1:int,2:bool} [name, indexToResumeFrom, opensBlock]
     */
    private function readNamespaceName(array $tokens, int $startIndex): array
    {
        $name = '';
        $count = count($tokens);

        for ($i = $startIndex; $i < $count; $i++) {
            $token = $tokens[$i];

            if (is_string($token)) {
                if ($token === ';') {
                    return [$name, $i, false];
                }

                if ($token === '{') {
                    // Leave the '{' itself unconsumed so the main loop's
                    // brace-depth tracking sees it on the next iteration.
                    return [$name, $i - 1, true];
                }

                return [$name, $i - 1, false];
            }

            [$id, $text] = $token;

            if ($id === T_WHITESPACE || $id === T_COMMENT || $id === T_DOC_COMMENT) {
                continue;
            }

            if ($id === T_STRING || $id === T_NS_SEPARATOR
                || (defined('T_NAME_QUALIFIED') && $id === T_NAME_QUALIFIED)
                || (defined('T_NAME_FULLY_QUALIFIED') && $id === T_NAME_FULLY_QUALIFIED)
            ) {
                $name .= $text;
                continue;
            }

            return [$name, $i - 1, false];
        }

        return [$name, $count - 1, false];
    }
}
