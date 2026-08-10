<?php

namespace App\Statics;

trait BuilderTrait
{
    public function tap(): static
    {
        return $this;
    }
}

interface Buildable
{
    public function build(): static;
}

class Base
{
    public function make(): static
    {
        return $this;
    }

    public function pick(): static|false
    {
        return $this;
    }

    public function upper(): STATIC
    {
        return $this;
    }

    private function hidden(): static
    {
        return $this;
    }
}

class Concrete
{
    public function copy(): Concrete
    {
        return $this;
    }

    public function untyped()
    {
        return $this;
    }

    public function widened(): Concrete|false
    {
        return $this;
    }
}

final class StaticHelper
{
}

final class Factory
{
    /**
     * @return static
     */
    public function imported(): static
    {
        return $this;
    }

    public function maybe(): ?static
    {
        return $this;
    }

    public function either(): static|false
    {
        return $this;
    }

    public function loud(): STATIC
    {
        return $this;
    }

    public function pair(): self|false
    {
        return $this;
    }

    /**
     * @return array<int, static>
     */
    public function many(): array
    {
        return [];
    }

    /**
     * @return STATIC
     */
    public function shouted(): self
    {
        return $this;
    }

    /**
     * @phpstan-return static
     */
    public function tagged(): self
    {
        return $this;
    }

    public function plain(): self
    {
        return $this;
    }

    /**
     * Hand back the same instance.
     */
    public function undocumented(): self
    {
        return $this;
    }

    /**
     * @return self A static factory, described statically
     */
    public function described(): self
    {
        return $this;
    }

    /**
     * @return array<int, self> a static list of rows
     */
    public function rows(): array
    {
        return [];
    }

    /**
     * @return self (static, not late-bound)
     */
    public function bound(): self
    {
        return $this;
    }

    /**
     * @return \App\Statics\StaticHelper
     */
    public function helper(): StaticHelper
    {
        return new StaticHelper;
    }
}

final class Locked extends Base implements Buildable
{
    public function make(): static
    {
        return $this;
    }

    public function pick(): static|false
    {
        return $this;
    }

    public function build(): static
    {
        return $this;
    }

    public function upper(): STATIC
    {
        return $this;
    }

    public function hidden(): static
    {
        return $this;
    }
}

final class Copier extends Concrete
{
    public function copy(): static
    {
        return $this;
    }

    public function untyped(): static
    {
        return $this;
    }

    public function widened(): static
    {
        return $this;
    }
}

final class UsesTrait
{
    use BuilderTrait;
}

enum Suit
{
    case HEARTS;

    public function itself(): static
    {
        return $this;
    }
}

class Open
{
    public function chain(): static
    {
        return $this;
    }
}

abstract class AbstractBase
{
    public function chain(): static
    {
        return $this;
    }
}
