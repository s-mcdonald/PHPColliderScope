<?php

declare(strict_types=1);

namespace PHPColliderScope\PHPDocumentParser\Document;

enum SymbolKind: string
{
    case ClassType = 'class';
    case InterfaceType = 'interface';
    case TraitType = 'trait';
    case EnumType = 'enum';
    case FunctionType = 'function';

    public function isClassLike(): bool
    {
        return $this !== self::FunctionType;
    }

    public function collidesWithGroup(): string
    {
        return $this->isClassLike() ? 'type' : 'function';
    }
}
