<?php
require_once './app/CIDR.php';

/**
 * This is a bootstrapping class to run our CLI application
 */
class Ipv6CalcApp {
    /**
     * Initialize the main calculation class
     *
     * @return CIDR
     * @throws Exception
     */
    private function initCidr() {
        global $argv;

        if (!isset($argv[1]) || empty($argv[1])) {
            throw new Exception('You should provide an IPv6 CIDR as a parameter.');
        }

        return new CIDR($argv[1]);
    }

    /**
     * Run an application and display calculation result
     */
    public function run() {
        try {
            $cidr = $this->initCidr();
            $hostsRange = $cidr->getSubnetHostsRange();
            $this->printMessage(array(
                "Expanded Notation: {$cidr->getExpandedIp()}",
                "Condensed Notation: {$cidr->getCondensedIp()}",
                "Prefix Length: {$cidr->getPrefixLength()}",
                "Host Range: {$hostsRange[0]} - {$hostsRange[1]}",
                "Total number of hosts: {$cidr->getHostsCount()}",
            ));
        }
        catch (InvalidArgumentException $e) {
            $this->printMessage(array($e->getMessage()));
        }
        catch (Exception $e) {
            $this->printMessage(array('An unhandled error occurred.'));
        }
    }

    /**
     * @param array $message
     */
    private function printMessage(array $message) {
        echo implode("\n", $message) . "\n";
    }
}