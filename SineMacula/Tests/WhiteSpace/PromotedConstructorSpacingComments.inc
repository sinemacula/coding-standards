<?php

namespace App;

class TightDirectiveOnOpener
{
    public function __construct(// @phpstan-ignore-line

        public int $id,
    ) {
    }
}

class TrailingNoteOnOpener
{
    public function __construct(// padding note

        public int $id,
    ) {
    }
}

class DirectiveMentionOnOpener
{
    public function __construct(// see qlty-ignore: docs

        public int $id,
    ) {
    }
}

class DirectiveOnOwnLine
{
    public function __construct(
        // @phpstan-ignore-line

        public int $id,
    ) {
    }
}
