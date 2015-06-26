<?php

class CIDRTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        require_once '../CIDR.php';
    }

    public function testCorrectInstantiation() {
        $cidr = new CIDR('2001:0db8:85a3:08d3::0370:7334/64');
        $this->assertEquals('2001:0db8:85a3:08d3::0370:7334', $cidr->getIp());
        $this->assertEquals('64', $cidr->getPrefixLength());

        $cidr = new CIDR('2001:0db8:85a3:08d3::0370:7334/0');
        $this->assertEquals('2001:0db8:85a3:08d3::0370:7334', $cidr->getIp());
        $this->assertEquals('0', $cidr->getPrefixLength());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testEmptyPrefix() {
        new CIDR('2001:0db8:85a3:08d3::0370:7334/');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testWrongPrefix() {
        new CIDR('2001:0db8:85a3:08d3::0370:7334/131');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testIpWith3colons() {
        new CIDR('2001:0db8:85a3:::0370:7334/64');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testIpWith9parts() {
        new CIDR('2001:0db8:85a3:f019:85a3:0370:7334:a37b:1234/64');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testIpWithWrongCharacters() {
        new CIDR('2001:0db8:g5a3:f019:85a3:0370:7334:a37b/64');
    }

    public function testExpandedIpNotation() {
        $cidr = new CIDR('2001:db8:85a3:8d3::370:7334/64');
        $this->assertEquals('2001:0db8:85a3:08d3:0000:0000:0370:7334', $cidr->getExpandedIp());

        $cidr = new CIDR('2001:db8:85a3:8d3::7334/64');
        $this->assertEquals('2001:0db8:85a3:08d3:0000:0000:0000:7334', $cidr->getExpandedIp());

        $cidr = new CIDR('::2001:db8:85a3:8d3/64');
        $this->assertEquals('0000:0000:0000:0000:2001:0db8:85a3:08d3', $cidr->getExpandedIp());

        $cidr = new CIDR('2001:db8:85a3:8d3::/64');
        $this->assertEquals('2001:0db8:85a3:08d3:0000:0000:0000:0000', $cidr->getExpandedIp());

        $cidr = new CIDR('2001::/64');
        $this->assertEquals('2001:0000:0000:0000:0000:0000:0000:0000', $cidr->getExpandedIp());

        $cidr = new CIDR('::2001/64');
        $this->assertEquals('0000:0000:0000:0000:0000:0000:0000:2001', $cidr->getExpandedIp());

        $cidr = new CIDR('::/64');
        $this->assertEquals('0000:0000:0000:0000:0000:0000:0000:0000', $cidr->getExpandedIp());
    }

    public function testCondensedNotation() {
        $cidr = new CIDR('2001:0db8:85a3:08d3:0000:0370:7334:0000/64');
        $this->assertEquals('2001:db8:85a3:8d3::370:7334:0', $cidr->getCondensedIp());

        $cidr = new CIDR('2001:0db8:85a3:08d3::0370:7334/64');
        $this->assertEquals('2001:db8:85a3:8d3::370:7334', $cidr->getCondensedIp());

        $cidr = new CIDR('2001:0db8:85a3:08d3:0000:0000:0370:7334/64');
        $this->assertEquals('2001:db8:85a3:8d3::370:7334', $cidr->getCondensedIp());

        $cidr = new CIDR('2001:0db8:85a3:00d3:0000:0000:0000:0000/64');
        $this->assertEquals('2001:db8:85a3:d3::', $cidr->getCondensedIp());

        $cidr = new CIDR('0000:0000:0000:0000:0000:0db8:08d3:0000/64');
        $this->assertEquals('::db8:8d3:0', $cidr->getCondensedIp());

        $cidr = new CIDR('0000:0000:85a3:0000:0000:0000:0db8:08d3/64');
        $this->assertEquals('0:0:85a3::db8:8d3', $cidr->getCondensedIp());

        $cidr = new CIDR('85a3:0000:0000:0000:0000:0000:0000:0000/64');
        $this->assertEquals('85a3::', $cidr->getCondensedIp());

        $cidr = new CIDR('0000:0000:0000:0000:0000:0000:0000:0000/64');
        $this->assertEquals('::', $cidr->getCondensedIp());
    }

    public function testSubnetHostsRange() {
        $cidr = new CIDR('2001:db8:85a3:8d3::7334/6');
        $this->assertEquals(
            array('2000:0000:0000:0000:0000:0000:0000:0000', '23ff:ffff:ffff:ffff:ffff:ffff:ffff:ffff'),
            $cidr->getSubnetHostsRange()
        );

        $cidr = new CIDR('2d81:db8:85a3:8d3::7334/7');
        $this->assertEquals(
            array('2c00:0000:0000:0000:0000:0000:0000:0000', '2dff:ffff:ffff:ffff:ffff:ffff:ffff:ffff'),
            $cidr->getSubnetHostsRange()
        );

        $cidr = new CIDR('2d81:db8:85a3:8d3::7334/19');
        $this->assertEquals(
            array('2d81:0000:0000:0000:0000:0000:0000:0000', '2d81:1fff:ffff:ffff:ffff:ffff:ffff:ffff'),
            $cidr->getSubnetHostsRange()
        );

        $cidr = new CIDR('2d81:db8:85a3:8d3::7334/32');
        $this->assertEquals(
            array('2d81:0db8:0000:0000:0000:0000:0000:0000', '2d81:0db8:ffff:ffff:ffff:ffff:ffff:ffff'),
            $cidr->getSubnetHostsRange()
        );

        $cidr = new CIDR('2d81:db8:85a3:8d3::7334/0');
        $this->assertEquals(
            array('0000:0000:0000:0000:0000:0000:0000:0000', 'ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff'),
            $cidr->getSubnetHostsRange()
        );

        $cidr = new CIDR('2d81:db8:85a3:8d3::7334/128');
        $this->assertEquals(
            array('2d81:0db8:85a3:08d3:0000:0000:0000:7334', '2d81:0db8:85a3:08d3:0000:0000:0000:7334'),
            $cidr->getSubnetHostsRange()
        );
    }
}
