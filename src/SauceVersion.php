<?php

namespace PHPSauceConnect;

interface SauceVersion {
    # An implementation of this interface should be provided for each version present
    # At SauceLabs' download page: https://wiki.saucelabs.com/display/DOCS/Sauce+Connect+Proxy
    public function getBinaryName();
    public function getURL();
    public function getHash();
    public function Download();
    public function GetFullBinaryPath();
}