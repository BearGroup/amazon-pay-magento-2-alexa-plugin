<?php

namespace Amazon\Alexa\Controller\Adminhtml\Logs;

use Magento\Framework\Exception\NotFoundException;

class Download extends \Magento\Backend\Controller\Adminhtml\System
{
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    private $fileFactory;

    public function __construct(
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Backend\App\Action\Context $context
    )
    {
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        try {
            $log = $this->getRequest()->getParam('name');
            $logs = \Amazon\Alexa\Plugin\DeveloperLogs::LOGS;
            if (!isset($logs[$log])) {
                throw new \Exception('Log "' . $log . '" is not exist');
            }
            return $this->fileFactory->create(basename($logs[$log]['path']), [
                'type' => 'filename',
                'value' => $logs[$log]['path']
            ]);
        } catch (\Exception $e) {
            throw new NotFoundException($e->getMessage());
        }
    }
}
