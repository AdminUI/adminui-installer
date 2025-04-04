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
        $this->composer = config('adminui-installer.root') . '/lib/composer.phar';
    }

    public function run(string|array $command)
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
                return;
            } else {
                $process->run(fn($type, $line) => $output->push($line));
                return $output;
            }
        } catch (ProcessSignaledException $e) {
            $output->push($e->getMessage());

            return $output;
        }
    }

    protected function phpBinary()
    {
        return ProcessUtils::escapeArgument((new PhpExecutableFinder)->find(false));
    }

    /**
     * Get a new Symfony process instance.
     *
     * @return \Symfony\Component\Process\Process
     */
    protected function getProcess(array $command, array $env = [])
    {
        return (new Process($command, base_path(), $env))->setTimeout(null);
    }
}
