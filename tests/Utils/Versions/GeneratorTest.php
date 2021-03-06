<?php

namespace App\Tests\Utils\Versions;

use PHPUnit\Framework\TestCase;
use App\Utils\Versions\Generator;
use App\Utils\Versions\Client;
use App\Utils\Cache;

class GeneratorTest extends TestCase
{
    private $cacheDir = __DIR__.'/../../../var/cache/test';
    private $cache;
    private $client;
    private $generator;

    public function setUp()
    {
        $this->cache = new Cache($this->cacheDir);
        $this->cache->clear();

        // The results returned by the client depend on the remote URLs so we
        // mock the returned results.
        $this->client = $this->getMockBuilder(Client::class)
            ->setMethods(['fetchDevVersions', 'fetchStableVersions'])
            ->getMock();

        $this->client->expects($this->once())
            ->method('fetchDevVersions')
            ->will($this->returnValue(json_decode(file_get_contents(__DIR__.'/../../mock/responses/dev-body.txt', true))));

        $this->client->expects($this->once())
            ->method('fetchStableVersions')
            ->will($this->returnValue(json_decode(file_get_contents(__DIR__.'/../../mock/responses/stable-body.txt'), true)));

        $this->generator = $this->getMockBuilder(Generator::class)
            ->setConstructorArgs([$this->client, $this->cache])
            ->setMethods(['getAffixes'])
            ->getMock();

        // The extra versions are always date dependant so we mock it to include
        // static date done on the tests day.
        $date = '2018-12-26';
        $this->generator->expects($this->any())
            ->method('getAffixes')
            ->will($this->returnValue(['Git-'.$date.' (snap)', 'Git-'.$date.' (Git)',]));
    }

    public function tearDown()
    {
        $this->cache->clear();
        rmdir($this->cacheDir);
    }

    public function testVersions()
    {
        $versions = $this->generator->getVersions();

        $this->assertInternalType('array', $versions);
        $this->assertGreaterThan(5, count($versions));

        $fixture = require __DIR__.'/../../fixtures/versions/versions.php';
        $cached = require $this->cacheDir.'/versions.php';

        $this->assertEquals($fixture[1], $cached[1]);
        $this->assertContains('Next Major Version', $versions);
        $this->assertContains('Irrelevant', $versions);
        $this->assertContains('7.2.14RC1', $versions);
    }
}
