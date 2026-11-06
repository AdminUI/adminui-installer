<?php

namespace AdminUI\AdminUIInstaller\Traits;

use AdminUI\AdminUIInstaller\Facades\Json;
use Closure;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Runs an install step and streams its console output to the browser as newline-delimited JSON:
 *   {"type":"log","line":"..."}      zero or more, as the step produces output
 *   {"type":"done","status":{...}}   the step succeeded; carries the latest install status
 *   {"type":"error","message":"..."} the step failed
 */
trait StreamsStepOutput
{
    /**
     * @param  Closure(callable(string): void): mixed  $work  receives a callback that accepts raw output text
     */
    protected function streamStep(Closure $work): StreamedResponse
    {
        return response()->stream(function () use ($work) {
            @set_time_limit(0);
            ignore_user_abort(true);

            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            $emit = function (array $event) {
                echo json_encode($event, JSON_INVALID_UTF8_SUBSTITUTE)."\n";
                flush();
            };

            // Some servers hold back output until a minimum amount has been sent
            echo str_repeat(' ', 2048)."\n";
            flush();

            $pending = '';
            $log = function (string $text) use (&$pending, $emit) {
                $pending .= str_replace("\r\n", "\n", $text);

                while (($pos = strpos($pending, "\n")) !== false) {
                    $line = rtrim(substr($pending, 0, $pos));
                    $pending = substr($pending, $pos + 1);

                    if ($line !== '') {
                        $emit(['type' => 'log', 'line' => $line]);
                    }
                }
            };

            try {
                $work($log);
                $log("\n");
                $emit(['type' => 'done', 'status' => Json::get()]);
            } catch (Throwable $e) {
                $log("\n");
                $emit(['type' => 'error', 'message' => $e->getMessage()]);
            }
        }, 200, [
            'Content-Type' => 'application/x-ndjson',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
