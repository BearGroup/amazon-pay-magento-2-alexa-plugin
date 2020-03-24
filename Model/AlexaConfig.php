<?php
/**
 * Copyright © Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 *  http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

namespace Amazon\Alexa\Model;

use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Cache\Type\Config as CacheTypeConfig;
use Zend\Crypt\PublicKey\RsaOptions;

class AlexaConfig
{
    /**
     * @var \Amazon\Alexa\Logger\AlexaLogger
     */
    private $alexaLogger;

    /**
     * @var \Magento\Framework\App\Config\ConfigResource\ConfigInterface
     */
    private $config;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    /**
     * @var \Magento\Framework\App\Cache\Manager
     */
    private $cacheManager;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    private $moduleManager;

    /**
     * AlexaConfig constructor.
     * @param \Amazon\Alexa\Logger\AlexaLogger $alexaLogger
     * @param \Magento\Framework\App\Config\ConfigResource\ConfigInterface $config
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Cache\Manager $cacheManager
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     */
    public function __construct(
        \Amazon\Alexa\Logger\AlexaLogger $alexaLogger,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $config,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Cache\Manager $cacheManager,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        $this->alexaLogger  = $alexaLogger;
        $this->config       = $config;
        $this->scopeConfig  = $scopeConfig;
        $this->cacheManager = $cacheManager;
        $this->moduleManager = $moduleManager;
        $this->encryptor    = $encryptor;
    }

    /**
     * @return \Amazon\PayV2\Model\AmazonConfig
     */
    protected function getPaymentConfig()
    {
        $result = null;
        if ($this->moduleManager->isEnabled('Amazon_PayV2')) {
            try {
                $result = \Magento\Framework\App\ObjectManager::getInstance()->get('Amazon\PayV2\Model\AmazonConfig');
            } catch (\Exception $e) {
                $this->alexaLogger->addWarning($e->getMessage());
            }
        }
        return $result;
    }

    /**
     * @param string $scope
     * @param null $scopeCode
     * @return bool
     */
    protected function usePaymentCredentials($scope = ScopeInterface::SCOPE_STORE, $scopeCode = null)
    {
        $result = false;
        $config = $this->getPaymentConfig();
        if ($config) {
            $result = $config->isEnabled($scope, $scopeCode);
        }
        return $result;
    }

    /**
     * @param string $scope
     * @param null $scopeCode
     * @return string|null
     */
    protected function getPaymentPrivateKey($scope = ScopeInterface::SCOPE_STORE, $scopeCode = null)
    {
        $result = null;
        $config = $this->getPaymentConfig();
        if ($config) {
            $result = $config->getPrivateKey($scope, $scopeCode);
        }
        return $result;
    }

    /**
     * @param string $scope
     * @param null $scopeCode
     * @return string|null
     */
    protected function getPaymentPublicKeyId($scope = ScopeInterface::SCOPE_STORE, $scopeCode = null)
    {
        $result = null;
        $config = $this->getPaymentConfig();
        if ($config) {
            $result = $config->getPublicKeyId($scope, $scopeCode);
        }
        return $result;
    }

    /**
     * Check to Alexa Delivery Notifications is enabled
     *
     * @param string $scope
     * @param null $scopeCode
     * @param null $store
     *
     * @return bool
     */
    public function isAlexaEnabled($scope = ScopeInterface::SCOPE_STORE, $scopeCode = null)
    {
        return $this->scopeConfig->getValue(
            'payment/amazon_payment/alexa_active',
            $scope,
            $scopeCode
        );
    }

    /**
     * Return Alexa Private Key
     *
     * @param string $scope
     * @param null $scopeCode
     * @param null $store
     *
     * @return string
     */
    public function getAlexaPrivateKey($scope = ScopeInterface::SCOPE_STORE, $scopeCode = null)
    {
        return $this->usePaymentCredentials($scope, $scopeCode) ? $this->getPaymentPrivateKey($scope, $scopeCode) : $this->scopeConfig->getValue(
            'payment/amazon_payment/alexa_private_key',
            $scope,
            $scopeCode
        );
    }

    /**
     * Return Alexa Public Key
     *
     * @param string $scope
     * @param null $scopeCode
     * @param null $store
     *
     * @return string
     */
    public function getAlexaPublicKey($scope = ScopeInterface::SCOPE_STORE, $scopeCode = null)
    {
        return $this->scopeConfig->getValue(
            'payment/amazon_payment/alexa_public_key',
            $scope,
            $scopeCode
        );
    }

    /**
     * Return Alexa Public Key ID
     *
     * @param string $scope
     * @param null $scopeCode
     * @param null $store
     *
     * @return string
     */
    public function getAlexaPublicKeyId($scope = ScopeInterface::SCOPE_STORE, $scopeCode = null)
    {
        return $this->usePaymentCredentials($scope, $scopeCode) ? $this->getPaymentPublicKeyId($scope, $scopeCode) : $this->scopeConfig->getValue(
            'payment/amazon_payment/alexa_public_key_id',
            $scope,
            $scopeCode
        );
    }

    /**
     * Generate and save new public/private keys
     */
    public function generateKeys()
    {
        $rsa = new RsaOptions();
        $rsa->generateKeys(array(
            'private_key_bits' => 2048,
        ));

        $encrypt = $this->encryptor->encrypt((string) $rsa->getPrivateKey());

        $this->config
            ->saveConfig('payment/amazon_payment/alexa_public_key', (string) $rsa->getPublicKey())
            ->saveConfig('payment/amazon_payment/alexa_private_key', $encrypt);

        $this->cacheManager->clean([CacheTypeConfig::TYPE_IDENTIFIER]);
    }
}
