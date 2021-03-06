<?php
/**
 * @see       https://github.com/zendframework/ZendService_Amazon for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/ZendService_Amazon/blob/master/LICENSE.md New BSD License
 */

namespace ZendServiceTest\Amazon\Ec2;

use PHPUnit\Framework\TestCase;
use ZendService\Amazon\Ec2;
use Zend\Http\Client as HttpClient;
use Zend\Http\Client\Adapter\Test as HttpClientTestAdapter;

/**
 * ZendService\Amazon\Ec\Region test case.
 *
 * @category   Zend
 * @package    Zend_Service_Amazon
 * @subpackage UnitTests
 * @group      Zend_Service
 * @group      Zend_Service_Amazon
 * @group      Zend_Service_Amazon_Ec2
 */
class RegionTest extends TestCase
{

    /**
     * @var Zend_Service_Amazon_Ec2_Availabilityzones
     */
    private $regionInstance;

    /**
     * @var HttpClient
     */
    protected $httpClient = null;

    /**
     * @var HttpClientTestAdapter
     */
    protected $httpClientTestAdapter = null;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        $this->httpClientTestAdapter = new HttpClientTestAdapter;
        $this->httpClient = new HttpClient(null, ['adapter' => $this->httpClientTestAdapter]);
        $this->regionInstance = new Ec2\Region('access_key', 'secret_access_key', null, $this->httpClient);
    }

    public function testDescribeSingleRegion()
    {
        $rawHttpResponse = "HTTP/1.1 200 OK\r\n"
                    . "Date: Fri, 24 Oct 2008 17:24:52 GMT\r\n"
                    . "Server: hi\r\n"
                    . "Last-modified: Fri, 24 Oct 2008 17:24:52 GMT\r\n"
                    . "Status: 200 OK\r\n"
                    . "Content-type: application/xml; charset=utf-8\r\n"
                    . "Expires: Tue, 31 Mar 1981 05:00:00 GMT\r\n"
                    . "Connection: close\r\n"
                    . "\r\n"
                    . "<DescribeRegionsResponse xmlns=\"http://ec2.amazonaws.com/doc/2009-04-04/\">\r\n"
                    . "  <regionInfo>\r\n"
                    . "    <item>\r\n"
                    . "      <regionName>us-east-1</regionName>\r\n"
                    . "      <regionUrl>us-east-1.ec2.amazonaws.com</regionUrl>\r\n"
                    . "    </item>\r\n"
                    . "  </regionInfo>\r\n"
                    . "</DescribeRegionsResponse>";
        $this->httpClientTestAdapter->setResponse($rawHttpResponse);

        $response = $this->regionInstance->describe('us-east-1');

        $arrRegion = [
            [
                'regionName'    => 'us-east-1',
                'regionUrl'     => 'us-east-1.ec2.amazonaws.com'
            ]
        ];

        $this->assertSame($arrRegion, $response);
    }

    public function testDescribeMultipleRegions()
    {
        $rawHttpResponse = "HTTP/1.1 200 OK\r\n"
                    . "Date: Fri, 24 Oct 2008 17:24:52 GMT\r\n"
                    . "Server: hi\r\n"
                    . "Last-modified: Fri, 24 Oct 2008 17:24:52 GMT\r\n"
                    . "Status: 200 OK\r\n"
                    . "Content-type: application/xml; charset=utf-8\r\n"
                    . "Expires: Tue, 31 Mar 1981 05:00:00 GMT\r\n"
                    . "Connection: close\r\n"
                    . "\r\n"
                    . "<DescribeRegionsResponse xmlns=\"http://ec2.amazonaws.com/doc/2009-04-04/\">\r\n"
                    . "  <regionInfo>\r\n"
                    . "    <item>\r\n"
                    . "      <regionName>us-east-1</regionName>\r\n"
                    . "      <regionUrl>us-east-1.ec2.amazonaws.com</regionUrl>\r\n"
                    . "    </item>\r\n"
                    . "    <item>\r\n"
                    . "      <regionName>us-west-1</regionName>\r\n"
                    . "      <regionUrl>us-west-1.ec2.amazonaws.com</regionUrl>\r\n"
                    . "    </item>\r\n"
                    . "  </regionInfo>\r\n"
                    . "</DescribeRegionsResponse>";
        $this->httpClientTestAdapter->setResponse($rawHttpResponse);

        $response = $this->regionInstance->describe(['us-east-1', 'us-west-1']);

        $arrRegion = [
            [
                'regionName'    => 'us-east-1',
                'regionUrl'     => 'us-east-1.ec2.amazonaws.com'
            ],
            [
                'regionName'    => 'us-west-1',
                'regionUrl'     => 'us-west-1.ec2.amazonaws.com'
            ]
        ];

        $this->assertSame($arrRegion, $response);
    }
}
