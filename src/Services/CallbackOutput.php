<?php

namespace AdminUI\AdminUIInstaller\Services;

use Symfony\Component\Console\Output\Output;

/**
 * Console output that hands every write to a callback as it happens, while also keeping the full text.
 */
class CallbackOutput extends Output
{
    protected string $buffer = '';

    protected $callback;

    public function __construct(?callable $callback = null)
    {
        parent::__construct(self::VERBOSITY_NORMAL, false);
        $this->callback = $callback;
    }

    public function fetch(): string
    {
        return $this->buffer;
    }

    protected function doWrite(string $message, bool $newline): void
    {
        $message .= $newline ? "\n" : '';
        $this->buffer .= $message;

        if ($this->callback !== null) {
            ($this->callback)($message);
        }
    }
}
