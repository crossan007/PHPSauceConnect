<?php

namespace PHPSauceConnect;

class WindowsSauce implements SauceVersion{
    private $scBasePath;

    public function __construct($basePath){ 
        $this->scBasePath = $basePath;
        if (!file_exists($this->GetFullBinaryPath())) {
            $this->Download();
        }
        ConsoleWriteLine("Using Windows SauceConnect at: ".$this->GetFullBinaryPath());
    }

    public function GetFullBinaryPath() {
        return $this->scBasePath.DIRECTORY_SEPARATOR."sc-4.5.4-win32".DIRECTORY_SEPARATOR."bin".DIRECTORY_SEPARATOR."sc.exe";
    }
    public function getBinaryName() {
        return "sc.exe";
    }
    public function getURL() {
        return "https://saucelabs.com/downloads/sc-4.5.4-win32.zip";
    }
    public function getHash() {
        return "1d908877b04bdd7706d8b2b9caba2066dd146644";
    }

    function Download() {
        ConsoleWriteLine("SauceConnect not present.  Downloading");
        $tempzip = $this->scBasePath.DIRECTORY_SEPARATOR.$this->getBinaryName().".zip";
        file_put_contents($tempzip,fopen($this->getURL(),"r"));
        $zip = new ZipArchive;
        $res = $zip->open($tempzip);
        if ($res === TRUE) {
            $zip->extractTo($this->scBasePath);
            $zip->close();
            ConsoleWriteLine("SauceConnect Downloaded");
        } else {
            throw new \Exception("Unable to download SauceConnect");
        }
    }
}
