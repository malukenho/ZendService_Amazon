<?php
/**
 * @see       https://github.com/zendframework/ZendService_Amazon for the canonical source repository
 * @copyright Copyright (c) 2005-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/ZendService_Amazon/blob/master/LICENSE.md New BSD License
 */

namespace ZendService\Amazon\Ec2;

use Zend\Crypt\Hmac;

/**
 * An Amazon EC2 interface that allows yout to run, terminate, reboot and describe Amazon
 * Ec2 Instances.
 *
 * @category   Zend
 * @package    Zend_Service_Amazon
 * @subpackage Ec2
 */
class WindowsInstance extends AbstractEc2
{
    /**
     * Bundles an Amazon EC2 instance running Windows
     *
     * @param string $instanceId        The instance you want to bundle
     * @param string $s3Bucket          Where you want the ami to live on S3
     * @param string $s3Prefix          The prefix you want to assign to the AMI on S3
     * @param integer $uploadExpiration The expiration of the upload policy.  Amazon recommends 12 hours or longer.
     *                                  This is based in nubmer of minutes. Default is 1440 minutes (24 hours)
     * @return array                    containing the information on the new bundle operation
     */
    public function bundle($instanceId, $s3Bucket, $s3Prefix, $uploadExpiration = 1440)
    {
        $params = [];
        $params['Action'] = 'BundleInstance';
        $params['InstanceId'] = $instanceId;
        $params['Storage.S3.AWSAccessKeyId'] = $this->_getAccessKey();
        $params['Storage.S3.Bucket'] = $s3Bucket;
        $params['Storage.S3.Prefix'] = $s3Prefix;
        $uploadPolicy = $this->_getS3UploadPolicy($s3Bucket, $s3Prefix, $uploadExpiration);
        $params['Storage.S3.UploadPolicy'] = $uploadPolicy;
        $params['Storage.S3.UploadPolicySignature'] = $this->_signS3UploadPolicy($uploadPolicy);

        $response = $this->sendRequest($params);

        $xpath = $response->getXPath();

        $return = [];
        $return['instanceId'] = $xpath->evaluate('string(//ec2:bundleInstanceTask/ec2:instanceId/text())');
        $return['bundleId'] = $xpath->evaluate('string(//ec2:bundleInstanceTask/ec2:bundleId/text())');
        $return['state'] = $xpath->evaluate('string(//ec2:bundleInstanceTask/ec2:state/text())');
        $return['startTime'] = $xpath->evaluate('string(//ec2:bundleInstanceTask/ec2:startTime/text())');
        $return['updateTime'] = $xpath->evaluate('string(//ec2:bundleInstanceTask/ec2:updateTime/text())');
        $return['progress'] = $xpath->evaluate('string(//ec2:bundleInstanceTask/ec2:progress/text())');
        $return['storage']['s3']['bucket'] = $xpath
            ->evaluate('string(//ec2:bundleInstanceTask/ec2:storage/ec2:S3/ec2:bucket/text())');
        $return['storage']['s3']['prefix'] = $xpath
            ->evaluate('string(//ec2:bundleInstanceTask/ec2:storage/ec2:S3/ec2:prefix/text())');

        return $return;
    }

    /**
     * Cancels an Amazon EC2 bundling operation
     *
     * @param string $bundleId The ID of the bundle task to cancel
     * @return array           Information on the bundle task
     */
    public function cancelBundle($bundleId)
    {
        $params = [];
        $params['Action'] = 'CancelBundleTask';
        $params['BundleId'] = $bundleId;

        $response = $this->sendRequest($params);

        $xpath = $response->getXPath();

        $return = [];
        $return['instanceId'] = $xpath->evaluate('string(//ec2:bundleInstanceTask/ec2:instanceId/text())');
        $return['bundleId'] = $xpath->evaluate('string(//ec2:bundleInstanceTask/ec2:bundleId/text())');
        $return['state'] = $xpath->evaluate('string(//ec2:bundleInstanceTask/ec2:state/text())');
        $return['startTime'] = $xpath->evaluate('string(//ec2:bundleInstanceTask/ec2:startTime/text())');
        $return['updateTime'] = $xpath->evaluate('string(//ec2:bundleInstanceTask/ec2:updateTime/text())');
        $return['progress'] = $xpath->evaluate('string(//ec2:bundleInstanceTask/ec2:progress/text())');
        $return['storage']['s3']['bucket'] = $xpath
            ->evaluate('string(//ec2:bundleInstanceTask/ec2:storage/ec2:S3/ec2:bucket/text())');
        $return['storage']['s3']['prefix'] = $xpath
            ->evaluate('string(//ec2:bundleInstanceTask/ec2:storage/ec2:S3/ec2:prefix/text())');

        return $return;
    }

    /**
     * Describes current bundling tasks
     *
     * @param string|array $bundleId A single or a list of bundle tasks that you want
     *                               to find information for.
     * @return array                 Information for the task that you requested
     */
    public function describeBundle($bundleId = '')
    {
        $params = [];
        $params['Action'] = 'DescribeBundleTasks';

        if (is_array($bundleId) && ! empty($bundleId)) {
            foreach ($bundleId as $k => $name) {
                $params['bundleId.' . ($k + 1)] = $name;
            }
        } elseif (! empty($bundleId)) {
            $params['bundleId.1'] = $bundleId;
        }

        $response = $this->sendRequest($params);

        $xpath = $response->getXPath();

        $items = $xpath->evaluate('//ec2:bundleInstanceTasksSet/ec2:item');
        $return = [];

        foreach ($items as $item) {
            $i = [];
            $i['instanceId'] = $xpath->evaluate('string(ec2:instanceId/text())', $item);
            $i['bundleId'] = $xpath->evaluate('string(ec2:bundleId/text())', $item);
            $i['state'] = $xpath->evaluate('string(ec2:state/text())', $item);
            $i['startTime'] = $xpath->evaluate('string(ec2:startTime/text())', $item);
            $i['updateTime'] = $xpath->evaluate('string(ec2:updateTime/text())', $item);
            $i['progress'] = $xpath->evaluate('string(ec2:progress/text())', $item);
            $i['storage']['s3']['bucket'] = $xpath->evaluate('string(ec2:storage/ec2:S3/ec2:bucket/text())', $item);
            $i['storage']['s3']['prefix'] = $xpath->evaluate('string(ec2:storage/ec2:S3/ec2:prefix/text())', $item);

            $return[] = $i;
            unset($i);
        }


        return $return;
    }

    // TODO: Unsuppress standards checking when underscores removed from method names
    // @codingStandardsIgnoreStart
    
    /**
     * Generates the S3 Upload Policy Information
     *
     * @param string $bucketName        Which bucket you want the ami to live in on S3
     * @param string $prefix            The prefix you want to assign to the AMI on S3
     * @param integer $expireInMinutes  The expiration of the upload policy.  Amazon recommends 12 hours or longer.
     *                                  This is based in nubmer of minutes. Default is 1440 minutes (24 hours)
     * @return string                   Base64 encoded string that is the upload policy
     * @deprecated Underscore should be removed from method name
     */
    protected function _getS3UploadPolicy($bucketName, $prefix, $expireInMinutes = 1440)
    {
        $arrParams = [];
        $arrParams['expiration'] = gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", (time() + ($expireInMinutes * 60)));
        $arrParams['conditions'][] = ['bucket' => $bucketName];
        $arrParams['conditions'][] = ['acl' => 'ec2-bundle-read'];
        $arrParams['conditions'][] = ['starts-with', '$key', $prefix];

        return base64_encode(\Zend\Json\Json::encode($arrParams));
    }

    /**
     * Signed S3 Upload Policy
     *
     * @param string $policy Base64 Encoded string that is the upload policy
     * @return string        SHA1 encoded S3 Upload Policy
     * @deprecated Underscore should be removed from method name
     */
    protected function _signS3UploadPolicy($policy)
    {
        return Hmac::compute($this->_getSecretKey(), 'SHA1', $policy, Hmac::OUTPUT_BINARY);
    }
    // @codingStandardsIgnoreEnd
}
