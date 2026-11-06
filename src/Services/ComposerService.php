<?php

namespace AdminUI\AdminUIInstaller\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\ProcessUtils;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class ComposerService
{
    public $composer;

    public function __construct()
    {
        $this->composer = config('adminui-installer.root').'/lib/composer.phar';
    }

    public function run(string|array $command, ?callable $onOutput = null)
    {
        $output = collect([]);
        $stack = collect([$this->phpBinary(), $this->composer]);
        $command = Arr::wrap($command);
        $stack->push(...$command);
        $stack = $stack->flatten();

        $process = Process::fromShellCommandline($stack->implode(' '), base_path(), null, null, 300);

        try {
            if (function_exists('posix_isatty') && defined('STDOUT') && posix_isatty(STDOUT)) {
                $process->setTty(true);
                $process->run(function ($type, $buffer) {
                    echo $buffer;
                });

                if (extension_loaded('pcntl')) {
                    $stop = function () use ($process) {
                        $process->stop();
                    };
                    pcntl_async_signals(true);
                    pcntl_signal(SIGINT, $stop);
                    pcntl_signal(SIGQUIT, $stop);
                    pcntl_signal(SIGTERM, $stop);
                }
                $this->assertSucceeded($process, $output);

                return;
            } else {
                // Composer's scripts (e.g. `artisan package:discover --ansi`) force colour even though we pass
                // --no-ansi, so strip the escape codes before they reach the log shown in the browser
                $carry = '';
                $process->run(function ($type, $chunk) use ($output, $onOutput, &$carry) {
                    $line = $this->stripAnsi($chunk, $carry);
                    if ($line === '') {
                        return;
                    }
                    $output->push($line);
                    if ($onOutput !== null) {
                        $onOutput($line);
                    }
                });
                $this->assertSucceeded($process, $output);

                return $output;
            }
        } catch (ProcessSignaledException $e) {
            $output->push($e->getMessage());

            return $output;
        }
    }

    /**
     * Remove ANSI escape sequences (colours, cursor movement) from a chunk of process output.
     *
     * Process output arrives in arbitrary chunks, so a sequence can be split across two of them. An incomplete
     * trailing sequence is held back in $carry and prepended to the next chunk.
     */
    protected function stripAnsi(string $chunk, string &$carry): string
    {
        $chunk = $carry.$chunk;
        $carry = '';

        if (preg_match('/\e(?:\[[0-9;?]*[ -\/]*)?$/', $chunk, $match, PREG_OFFSET_CAPTURE)) {
            $carry = $match[0][0];
            $chunk = substr($chunk, 0, $match[0][1]);
        }

        return preg_replace('/\e\[[0-9;?]*[ -\/]*[@-~]/', '', $chunk) ?? $chunk;
    }

    /**
     * A failed composer run must stop the install rather than letting later steps run against a half-installed app
     */
    protected function assertSucceeded(Process $process, $output): void
    {
        if ($process->isSuccessful()) {
            return;
        }

        $detail = trim($output->implode(''));
        $detail = $detail !== '' ? $detail : trim($process->getErrorOutput());

        throw new \RuntimeException(
            'Composer exited with code '.$process->getExitCode().($detail !== '' ? ":\n".$detail : '.')
        );
    }

    protected function phpBinary()
    {
        return ProcessUtils::escapeArgument((new PhpExecutableFinder)->find(false));
    }

    /**
     * Get a new Symfony process instance.
     *
     * @return Process
     */
    protected function getProcess(array $command, array $env = [])
    {
        return (new Process($command, base_path(), $env))->setTimeout(null);
    }
}
