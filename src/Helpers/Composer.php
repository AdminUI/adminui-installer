<?php


namespace AdminUI\AdminUIInstaller\Helpers;


class Composer extends \Illuminate\Support\Composer
{
    public function run(array $command, $callback = null)
    {
        // findComposer() resolves to composer's binary
        // $command is an array that looks like ['composer', 'some-composer-command']
        $command = array_merge($this->findComposer(), $command);

        // we then pass the command array to getProcess()
        // getProcess() returns a Symfony Process() instance, which runs the command for us in the shell
        // the run() method execute the composer command
        $this->setWorkingPath(base_path())->getProcess($command)->run(function ($type, $data) use ($callback) {
            // $type can be 'err' or 'out'
            // 'err' when there is an error
            // 'out' is stdout from the command

            // $data is the command output
            // ie whatever composer spits out when the command runs.
            if ($callback) $callback($data);
        }, [
            // we can pass in env var to the process instance here
            // setting any additional environmental variable to the process
            "PATH" => '$PATH:/usr/local/bin',
            'COMPOSER_HOME' => '$HOME/.config/composer'
        ]);
    }
}
