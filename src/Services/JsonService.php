<?php

namespace AdminUI\AdminUIInstaller\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;

class JsonService
{
    protected string $file;
    protected Filesystem $disk;

    public function __construct()
    {
        $this->disk = Storage::disk('local');
        $this->file = "adminui-installer/status.json";

        $oldPath = config('adminui-installer.root') . '/resources/status.json';
        if (file_exists($oldPath)) {
            $contents = file_get_contents($oldPath);
            $this->disk->put('adminui-installer/status.json', $contents);
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
        try {
            $string = $this->disk->get($this->file);
        } catch (\Exception $e) {
            return $this->getDefault();
        }
        if (empty($string)) {
            return $this->getDefault();
        }

        return json_decode($string, true);
    }

    public function set(array|object $json): void
    {
        $string = json_encode($json, JSON_PRETTY_PRINT);
        $this->checkStatusFile();
        $this->disk->put($this->file, $string);
    }

    public function getField(string $field): mixed
    {
        $array = $this->get();

        return Arr::get($array, $field);
    }

    public function setField(string $field, mixed $data): void
    {
        $array = $this->get();
        $array[$field] = $data;
        $this->set($array);
    }
}
