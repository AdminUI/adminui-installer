<?php

namespace AdminUI\AdminUIInstaller\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class JsonService
{
    protected string $file;

    public function __construct()
    {
        $this->file = config('adminui-installer.root') . '/resources/status.json';
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
