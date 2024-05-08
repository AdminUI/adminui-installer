<?php

namespace AdminUI\AdminUIInstaller\Actions;

class SaveLicenceKeyAction
{
    protected string $env;

    public function __construct()
    {
        $this->env = app()->environmentFilePath();
    }

    public function execute(string $key): bool
    {
        $inserts = [
            'ADMINUI_PREFIX' => 'admin',
            'ADMINUI_LICENCE_KEY' => $key,
        ];

        foreach ($inserts as $key => $value) {
            $this->setEnvironmentValue($key, $value);
        }

        return true;
    }

    private function setEnvironmentValue($key, $value)
    {
        if (file_exists($this->env)) {
            if (getenv($key)) {
                //replace variable if key exit
                file_put_contents($this->env, str_replace(
                    "$key=".getenv($key),
                    "$key=".'"'.$value.'"',
                    file_get_contents($this->env)
                ));
            } else {
                //set if variable key not exit
                $file = file($this->env);
                $file[] = PHP_EOL."$key=".'"'.$value.'"';
                file_put_contents($this->env, $file);
            }
        }
    }
}
