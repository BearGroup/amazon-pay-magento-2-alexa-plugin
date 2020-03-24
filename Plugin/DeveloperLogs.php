<?php

namespace Amazon\Alexa\Plugin;

use Magento\Framework\App\Filesystem\DirectoryList;

class DeveloperLogs
{
    const DOWNLOAD_PATH = 'amazon_alexa/logs/download';

    const LOGS = [
        'alexaDeliveryLog' => ['name' => 'Alexa Delivery Log', 'path' => \Amazon\Alexa\Logger\Handler\Alexa::FILENAME],
    ];

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    private $urlBuilder;

    /**
     * @param DirectoryList $directoryList
     * @param \Magento\Backend\Model\UrlInterface $urlBuilder
     */
    public function __construct(
        DirectoryList $directoryList,
        \Magento\Backend\Model\UrlInterface $urlBuilder
    )
    {
        $this->directoryList = $directoryList;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @return array
     */
    private function getLogFiles()
    {
        $links = [];
        $root = $this->directoryList->getPath(DirectoryList::ROOT);
        foreach (self::LOGS as $name => $data) {
            if (file_exists($root . $data['path'])) {
                $links[] = [
                    'name' => $data['name'],
                    'link' => $this->urlBuilder->getUrl(self::DOWNLOAD_PATH, [
                        'name' => $name,
                    ]),
                ];
            }
        }
        return $links;
    }

    /**
     * @param mixed $subject
     * @param string $result
     * @return mixed
     */
    public function afterGetLinks($subject, $result)
    {
        $links = $this->getLogFiles();
        if (!empty($links)) {
            if ($result == __('No logs are currently available.')) {
                $result = '';
            }
            foreach ($links as $link) {
                $result .= '<a href="' . $link['link'] . '">' . $link['name'] . '</a><br />';
            }
        }
        return $result;
    }
}
