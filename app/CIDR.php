<?php
/**
 * This class represents an IPv6 address in a CIDR notation and calculates all information about IP and a subnet
 */
class CIDR {
    private $ip, $prefixLength, $expandedIp;

    /**
     * @param $cidrString
     * @throws Exception
     */
    public function __construct($cidrString) {
        if (strpos($cidrString, '/') === false) {
            throw new InvalidArgumentException('Wrong CIDR format');
        }

        $cidrParts = explode('/', $cidrString);
        if (count($cidrParts) != 2) {
            throw new InvalidArgumentException('Wrong CIDR format');
        }

        if (!$this->validateIp($cidrParts[0])) {
            throw new InvalidArgumentException('Wrong IPv6 address');
        }

        if (!$this->validatePrefixLength($cidrParts[1])) {
            throw new InvalidArgumentException('Wrong subnet prefix length');
        }

        $this->ip = trim($cidrParts[0]);
        $this->prefixLength = trim($cidrParts[1]);
    }

    /**
     * @param $ip
     * @return bool
     */
    private function validateIp($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * @param $prefixLength
     * @return bool
     */
    private function validatePrefixLength($prefixLength) {
        if ($prefixLength == '') {
            return false;
        }

        return $prefixLength >= 0 && $prefixLength <= 128;
    }

    /**
     * @return mixed
     */
    public function getIp() {
        return $this->ip;
    }

    /**
     * @return mixed
     */
    public function getPrefixLength() {
        return $this->prefixLength;
    }

    /**
     * Return a number of hosts in a subnet with a given prefix length
     *
     * @return string
     */
    public function getHostsCount() {
        $hostsCount = pow(2, (128 - $this->getPrefixLength()));
        return number_format($hostsCount);
    }

    /**
     * Returns an expanded IP notation
     *
     * @return string
     */
    public function getExpandedIp() {
        // as we use this function in other multiple parts of an application, we cache it's result
        if (is_null($this->expandedIp)) {
            $ip = $this->getIp();

            // special case for ::
            if ($ip == '::') {
                $this->expandedIp = '0000:0000:0000:0000:0000:0000:0000:0000';
                return $this->expandedIp;
            }

            // if an IP starts from :: or ends by ::, we leave only one colon. This eases breaking into array
            if (substr($ip, 0, 2) == '::' || substr($ip, -2) == '::') {
                $ip = str_replace('::', ':', $ip);
            }

            // break IP on parts
            $parts = explode(':', $ip);
            $partsExpanded = array();
            foreach ($parts as $part) {
                if (strlen($part) == 0) {
                    // empty part means it's a zeros sequence notation ::, so we're filling missing IP parts by zeros
                    $zeroParts = 9 - count($parts);
                    for ($i = 0; $i < $zeroParts; $i++) {
                        $partsExpanded[] = '0000';
                    }
                } else {
                    // prepend IP part by zeros if needed
                    $partsExpanded[] = sprintf("%04s", $part);
                }
            }

            $this->expandedIp = implode(':', $partsExpanded);
        }

        return $this->expandedIp;
    }

    /**
     * Returns a condenses IP notation
     *
     * @return string
     */
    public function getCondensedIp() {
        // first get an expanded notation, it's easier to work with it
        $expandedIp = $this->getExpandedIp();

        // break IP on parts
        $parts = explode(':', $expandedIp);

        // here we'll store shorthanded IP parts
        $partsCondensed = array();

        /**
         * we need the following two variables to find a longest zeros sequence and to replace it with double-colon ::
         */
        $longestZeroSequenceLength = -1;
        $longestZeroSequenceKey = null;

        foreach ($parts as $partNumber => $part) {
            $part = ltrim($part, '0');

            if (strlen($part) == 0) {
                $part = '0';

                if (!isset($parts[$partNumber - 1]) || strlen(ltrim($parts[$partNumber - 1], '0')) > 0) {
                    $currentSequenceLength = 1;
                    $i = 1;
                    while (isset($parts[$partNumber + $i]) && strlen(ltrim($parts[$partNumber + $i], '0')) == 0) {
                        $i++;
                        $currentSequenceLength++;
                    }
                    if ($currentSequenceLength > $longestZeroSequenceLength) {
                        $longestZeroSequenceLength = $currentSequenceLength;
                        $longestZeroSequenceKey = $partNumber;
                    }
                }
            }

            $partsCondensed[] = $part;
        }

        if (!is_null($longestZeroSequenceKey)) {
            // if we found a longest zeros sequence, we remove this sequence from a condensed array
            array_splice($partsCondensed, $longestZeroSequenceKey, $longestZeroSequenceLength, ':');
            if (count($partsCondensed) == 1 && $partsCondensed[0] == ':') {
                // the special case for 0000:0000:0000:0000:0000:0000:0000:0000
                return '::';
            }
        }

        return str_replace(':::', '::', implode(':', $partsCondensed));
    }

    /**
     * @return array where first element is a starting IP and second element is an ending IP
     */
    public function getSubnetHostsRange() {
        $expandedIp = str_replace(':', '', $this->getExpandedIp());

        /**
         * We get an indivisible HEX part of a prefix and save it into $prefixHexPart
         */
        $prefixLength = $this->getPrefixLength();
        $prefixHexPartLength = floor($prefixLength / 4);
        $prefixHexPart = substr($expandedIp, 0, $prefixHexPartLength);

        // Now we look if a prefix has a binary part
        $prefixBinPartLength = $prefixLength % 4;
        if ($prefixBinPartLength == 0) {
            // No binary part, starting and ending IPs will have the same prefixes
            $minHexPrefix = $maxHexPrefix = $prefixHexPart;
        }
        else {
            /**
             * There's a binary part in prefix. So starting and ending IPs will have different prefixes.
             */
            $nextHexNumber = substr($expandedIp, $prefixHexPartLength, 1);
            $nextHexNumberDec = hexdec($nextHexNumber);
            $minBinValue = $nextHexNumberDec & ((0b1111 << (4 - $prefixBinPartLength)) & 0b1111);
            $maxBinValue = $nextHexNumberDec | (0b1111 >> $prefixBinPartLength);

            $minHexPrefix = $prefixHexPart . base_convert($minBinValue, 10, 16);
            $maxHexPrefix = $prefixHexPart . base_convert($maxBinValue, 10, 16);
        }

        /**
         * fill up a starting IP with "0"s, and an ending IP - with "f"s
         */
        $startIp = $minHexPrefix . str_repeat('0', 32 - strlen($minHexPrefix));
        $endIp = $maxHexPrefix . str_repeat('f', 32 - strlen($maxHexPrefix));

        /**
         * add colons to IPs
         */
        $startIp = trim(chunk_split($startIp, 4, ':'), ':');
        $endIp = trim(chunk_split($endIp, 4, ':'), ':');

        return array($startIp, $endIp);
    }
}