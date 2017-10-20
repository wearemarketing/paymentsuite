<?php


namespace PaymentSuite\PaylandsBundle\Tests\Services\Api;
use Http\Message\ResponseFactory;
use Http\Mock\Client;
use PaymentSuite\PaylandsBundle\Services\Api\ApiClient;
use PaymentSuite\PaylandsBundle\Services\Api\ApiRequestFactory;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ApiClientTest
 *
 * @author Santi Garcia <sgarcia@wearemarketing.com>, <sangarbe@gmail.com>
 */
class ApiClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function tryIsModeSandboxEnabled()
    {
        $httpClient = new Client($this->prophesize(ResponseFactory::class)->reveal());

        $apiRequestFactoryMock = $this->prophesize(ApiRequestFactory::class);

        $apiClient = new ApiClient($httpClient, $apiRequestFactoryMock->reveal(), true);

        $this->assertTrue($apiClient->isModeSandboxEnabled());

        $apiClient = new ApiClient($httpClient, $apiRequestFactoryMock->reveal(), false);

        $this->assertFalse($apiClient->isModeSandboxEnabled());
    }
}