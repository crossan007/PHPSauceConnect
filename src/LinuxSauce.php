<?php

namespace PHPSauceConnect;

class LinuxSauce implements SauceVersion{
    private $scBasePath;

    public function __construct($basePath){ 
        $this->scBasePath = $basePath;
        if (!file_exists($this->GetFullBinaryPath())) {
            $this->Download();
        }
        ConsoleWriteLine("Using Linux SauceConnect at: ".$this->GetFullBinaryPath());
    }

    public function GetFullBinaryPath() {
        return $this->scBasePath.DIRECTORY_SEPARATOR."sc-4.5.4-linux".DIRECTORY_SEPARATOR."bin".DIRECTORY_SEPARATOR."sc";
    }
    public function getBinaryName() {
        return "sc";
    }
    public function getURL() {
        return "https://saucelabs.com/downloads/sc-4.5.4-linux.tar.gz";
    }
    public function getHash() {
        return "dc5efcd2be24ddb099a85b923d6e754754651fa8";
    }

    function Download() {
        ConsoleWriteLine("SauceConnect not present.  Downloading");
        $tempgz = $this->scBasePath.DIRECTORY_SEPARATOR.$this->getBinaryName().".tar.gz";
        file_put_contents($tempgz,fopen($this->getURL(),"r"));
        $gz = new PharData($tempgz);
        if ($gz) {
            $gz->extractTo($this->scBasePath);
            ConsoleWriteLine("SauceConnect Downloaded");
        } else {
            throw new \Exception("Unable to download SauceConnect");
        }
    }
}