<?php

namespace PHPSauceConnect;
use PHPSauceConnect\SauceConnectSettings;

class SauceLabsSauceConnect {
    private $settings;
    private $scPipes;
    private $scProcessResource;
    private $scCWD;
    private $scENV;
    private $sauceVersion;

    public static function GetSauceInstance($Username, $Key) {
        $sauceSettings = new SauceConnectSettings();
        $sauceSettings->SauceUsername = $Username;
        $sauceSettings->SauceKey = $Key;
        return new SauceLabsSauceConnect($sauceSettings);
    }

    private function __construct(SauceConnectSettings $SauceConnectSettings) {
        $this->settings = $SauceConnectSettings;
        switch (PHP_OS) {
            case "WINNT":
                $this->sauceVersion = new WindowsSauce(sys_get_temp_dir());
                break;
            case "Linux":
                $this->sauceVersion = new LinuxSauce(sys_get_temp_dir());
                break;
            default:
                throw new \Exception("Unsupported OS for SauceConnect.  Have: " . PHP_OS);
        }
    }

    function __destruct() {
        $this->Disconnect();
    }

    public function Connect() {
        
        $command = $this->sauceVersion->GetFullBinaryPath()." -u \"" . $this->settings->SauceUsername . "\" -k \"". $this->settings->SauceKey."\"";
        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
            1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
            2 => array("pipe", "r") // stderr is a file to write to
         );

        $this->scProcessHandle = proc_open($command,$descriptorspec, $this->scPipes,$this->scCWD, $this->scENV);
        ConsoleWriteLine("Waiting for sauce tunnel");
        $sc=0;
        while(true) {
            $read = fread($this->scPipes[1], 2096);
            if (strpos($read,"Sauce Connect is up, you may start your tests.")){
                break;
            }
            elseif (strpos($read,"error")){
                throw new \Exception("Error opening SauceConnect Tunnel: " . $read);
            }
        }
        ConsoleWriteLine("Sauce Connect is up, you may start your tests.");
    }

    public function Disconnect(){
        ConsoleWriteLine("Disconnecting from SauceLabs");
        proc_terminate($this->scProcessHandle);
    }

    public function GetWDHost() {
        return $this->settings->SauceUsername.":".$this->settings->SauceKey."@localhost:4445/wd/hub";
    }
}