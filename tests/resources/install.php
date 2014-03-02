#!/usr/bin/env php
<?php
require_once __DIR__.'/../bootstrap.php';

/**
 * Install public keys in the current users authorized_hosts file to allow unit tests in '@group server' to run
 */
class installer
{
    protected $args;

    /**
     * Run the installer
     *
     * @param string[] $args
     * @return bool
     */
    public function run($args = null)
    {
        if ($args !== null) {
            $this->setArgs($args);
        }

        $force = $this->hasOption('-f'); // non-interactive mode

        // Check the user exists
        if (!file_exists($this->getHomeDir())) {
            $this->log("User directory does not exist '".$this->getHomeDir()."' - have you created the test user?");
            $this->log("Add a user `".properties::$user."` with password `".properties::$pass."`");
            return false;
        }

        // Check you are root
        if (trim(`whoami`) != 'root') {
            $this->log("You need to be root to do this");
            return false;
        }

        // User confirmation
        if (!$force && !$this->confirm()) {
            $this->log("Aborting");
            return false;
        }

        $this->log("Installing.. ", false);

        // Pub keys to install
        $files = [
            'dsa-nopw.pem.pub',
            'dsa-pw.pem.pub',
            'rsa-nopw.pem.pub',
            'rsa-pw.pem.pub',
        ];

        // Check ~/.ssh/ directory exists
        $ssh_dir = $this->getHomeDir().'/.ssh';
        if (!file_exists($ssh_dir)) {
            mkdir($ssh_dir, 0700, true);
            chown($ssh_dir, properties::$user);
            chgrp($ssh_dir, properties::$user);
        }

        // Check authorized_keys file exists
        $key_file = $this->getKeyFile();
        if (!file_exists($key_file)) {
            touch($key_file);
            chmod($key_file, 0600);
            chown($key_file, properties::$user);
            chgrp($key_file, properties::$user);
        }

        if (!is_writable($key_file)) {
            $this->log("No write access to key file, aborting");
            return false;
        }

        $base = __DIR__.'/';
        $fp = fopen($key_file, 'a');
        foreach ($files as $file) {
            fwrite($fp, trim(file_get_contents($base.$file))."\n");
        }

        fclose($fp);

        $this->log("done");

        return false;
    }

    /**
     * Check if the application was run with a given option
     *
     * @param $opt
     * @return bool
     */
    protected function hasOption($opt)
    {
        return in_array($opt, $this->getArgs());
    }

    /**
     * Get confirmation from the user to proceed
     *
     * @return bool
     */
    protected function confirm()
    {
        $getChar = function () {
            $fr    = fopen("php://stdin", "r");
            $input = trim(strtolower(fgets($fr, 2)));
            fclose($fr);
            return $input{0};
        };

        $this->log("Install test public keys to '".$this->getKeyFile()."'? [y/N]: ", false);

        return $getChar() == 'y';
    }

    /**
     * Get the authorized_keys file
     *
     * @return string
     */
    protected function getKeyFile()
    {
        return $this->getHomeDir().'/.ssh/authorized_keys';
    }

    /**
     * Get the home directory for the SSH user
     *
     * @return string
     */
    protected function getHomeDir()
    {
        return '/home/'.properties::$user;
    }

    /**
     * Write to console
     *
     * @param string $msg
     */
    protected function log($msg = '', $newline = true)
    {
        if ($newline) {
            echo $msg."\n";
        } else {
            echo $msg;
        }
    }

    /**
     * Set Args
     *
     * @param mixed $args
     * @return installer
     */
    public function setArgs($args)
    {
        $this->args = $args;
        return $this;
    }

    /**
     * Get Args
     *
     * @return mixed
     */
    public function getArgs()
    {
        return $this->args;
    }


}

$installer = new installer();
if (isset($_SERVER['argv'])) {
    $installer->setArgs($_SERVER['argv']);
}
return $installer->run();
