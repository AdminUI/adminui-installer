<?php

namespace AdminUI\AdminUIInstaller\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class JsonService
{
    protected string $file;

    protected Filesystem $disk;

    protected ?array $cache = null;

    public function __construct()
    {
        $this->disk = Storage::disk('local');
        $this->file = "adminui-installer/status.json";

        $oldPath = config('adminui-installer.root') . '/resources/status.json';
        if (file_exists($oldPath)) {
            $contents = file_get_contents($oldPath);
            $this->disk->put('adminui-installer/status.json', $contents);
            unlink($oldPath);
        }
        $this->checkStatusFile();
    }

    private function checkStatusFile()
    {
        if (!$this->disk->exists($this->file)) {
            $this->disk->put($this->file, json_encode($this->getDefault()));
        }
    }

    private function getDefault()
    {
        return [
            'saveKey' => false,
        ];
    }

    public function get(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        try {
            $string = $this->disk->get($this->file);
        } catch (\Exception $e) {
            return $this->cache = $this->getDefault();
        }
        if (empty($string)) {
            return $this->cache = $this->getDefault();
        }

        return $this->cache = json_decode($string, true);
    }

    public function set(array|object $json): void
    {
        $array = (array) $json;

        $this->cache = $array;

        $path = $this->disk->path($this->file);

        $fp = fopen($path, 'c+');

        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            fwrite($fp, json_encode($array, JSON_PRETTY_PRINT));
            fflush($fp);
            flock($fp, LOCK_UN);
        }

        fclose($fp);
    }

    public function getField(string $field): mixed
    {
        $array = $this->get();

        return Arr::get($array, $field);
    }

    public function setField(string $field, mixed $data): void
    {
        $array = $this->get();
        $array = Arr::set($array, $field, $data);
        $this->set($array);
    }
}
