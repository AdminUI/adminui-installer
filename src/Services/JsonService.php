<?php

namespace AdminUI\AdminUIInstaller\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class JsonService
{
    protected string $file;

    public function __construct()
    {
        $oldPath = config('adminui-installer.root') . '/resources/status.json';
        $path = storage_path('app/adminui-installer/status.json');
        if (file_exists($oldPath)) {
            Storage::move($oldPath, $path);
        }
        $this->file = $path;
        $this->checkStatusFile();
    }

    private function checkStatusFile()
    {
        if (!Storage::exists($this->file)) {
            Storage::put($this->file, json_encode($this->getDefault()));
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
            $string = file_get_contents($this->file);
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
        file_put_contents($this->file, $string);
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
