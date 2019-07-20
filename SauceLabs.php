<?php
if (!function_exists("ConsoleWriteLine")) {
    function ConsoleWriteLine($string) {
        echo $string."\n";
    }
}

class SauceConectSettings {
    public $SauceUsername;
    public $SauceKey;
}

interface SauceVersion {
    # An implementation of this interface should be provided for each version present
    # At SauceLabs' download page: https://wiki.saucelabs.com/display/DOCS/Sauce+Connect+Proxy
    public function getBinaryName();
    public function getURL();
    public function getHash();
    public function Download();
    public function GetFullBinaryPath();
}

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

class SauceLabsSauceConnect {
    private $settings;
    private $scPipes;
    private $scProcessResource;
    private $scCWD;
    private $scENV;
    private $sauceVersion;

    public static function GetSauceInstance($Username, $Key) {
        $sauceSettings = new SauceConectSettings();
        $sauceSettings->SauceUsername = $Username;
        $sauceSettings->SauceKey = $Key;
        return new SauceLabsSauceConnect($sauceSettings);
    }

    private function __construct(SauceConectSettings $SauceConnectSettings) {
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
        }
        ConsoleWriteLine("Sauce Connect is up, you may start your tests.");
    }

    public function Disconnect(){
        ConsoleWriteLine("Disconnecting from SauceLabs");
        proc_terminate($this->scProcessHandle);
    }

    public function GetWDHost() {
        return $this->settings->SauceUsername.":".$this->settings->SauceKey."@ondemand.saucelabs.com/wd/hub";
    }
}