<?php
/**
 * This file is part of Oyst_OneClick for Magento.
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @author Oyst <plugin@oyst.com> <@oyst>
 * @category Oyst
 * @package Oyst_OneClick
 * @copyright Copyright (c) 2017 Oyst (http://www.oyst.com)
 */

/**
 * Adminhtml Actions Controller
 */
class Oyst_OneClick_Adminhtml_OneClick_ActionsController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @var Varien_Io_File
     */
    private $varienIo;

    /**
     * Maximum download size
     *
     * @var int
     */
    private $maxDownloadSize = 12600000; // 12Mo

    /**
     * Temporary files path.
     *
     * @var array
     */
    private $tempFilePath;

    /**
     * Base directory.
     *
     * @var string
     */
    private $baseDir;

    /**
     * Test if user can access to this sections
     *
     * @return bool
     *
     * @see Mage_Adminhtml_Controller_Action::_isAllowed()
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('oyst/oyst_oneclick/actions');
    }

    /**
     * Skip setup by setting the config flag accordingly
     */
    public function skipAction()
    {
        /** @var Oyst_OneClick_Helper_Data $helper */
        $helper = Mage::helper('oyst_oneclick');
        $identifier = $this->getRequest()->getParam('identifier');
        $helper->setIsInitialized($identifier);
        $this->_redirectReferer();
    }

    /**
     * Download requested log file
     */
    public function downloadAction()
    {
        $params = Mage::app()->getRequest()->getParams();
        $this->baseDir = Mage::getBaseDir('var') . DS . 'log' . DS;
        $filePath = $this->baseDir . $params['name'];

        $this->varienIo = new Varien_Io_File();
        $this->missingFile($filePath, $params['name']);

        if ($this->varienIo->fileExists($filePath) && Zend_Loader::isReadable($filePath)) {
            $content = $this->getFileContent($filePath);
            $tempFileName = $this->createTemporaryFile($params['name'], $content);
            // @codingStandardsIgnoreLine
            $file = file($this->tempFilePath[$tempFileName]);
            $rows = count($file);
            if (isset($params['rows']) && $rows > $params['rows']) {
                $rows -= $params['rows'];
                $file = array_slice($file, $rows);
                $content = implode('', $file);
                $this->deleteTemporaryFile($tempFileName);
                $tempFileName = $this->createTemporaryFile($tempFileName, $content);
            }

            $data = array(
                'type' => 'filename',
                'value' => $this->tempFilePath[$tempFileName],
                'rm' => true
            );

            $this->_prepareDownloadResponse($params['name'], $data);
        }
    }

    /**
     * Create temporary file.
     *
     * @param $fileName
     * @param $content
     *
     * @return string
     */
    private function createTemporaryFile($fileName, $content)
    {
        $tempFileName = rand(1, 9) . $fileName;

        $this->tempFilePath[$tempFileName] = $this->baseDir . $tempFileName;
        $this->varienIo->write($this->tempFilePath[$tempFileName], $content, 'w');

        return $tempFileName;
    }

    /**
     * Get maximum allowed content.
     *
     * @param $filePath
     *
     * @return string
     */
    private function getFileContent($filePath)
    {
        $fileParser = new Zend_Pdf_FileParserDataSource_File($filePath);
        $pointer = $fileParser->getSize() - $this->maxDownloadSize;

        if ($pointer > 0) {
            $fileParser->moveToOffset($pointer);
            $content = $fileParser->readBytes($this->maxDownloadSize);
        } else {
            $content = $fileParser->readAllBytes();
        }

        return $content;
    }

    /**
     * Delete temporary file.
     *
     * @param $tempFileName
     */
    private function deleteTemporaryFile($tempFileName)
    {
        $this->varienIo->rm($this->tempFilePath[$tempFileName]);
    }

    /**
     * Delete requested log file
     */
    public function deleteAction()
    {
        $params = Mage::app()->getRequest()->getParams();
        $fileName = Mage::getBaseDir('var') . DS . 'log' . DS . $params['name'];

        $this->varienIo = new Varien_Io_File();
        $this->missingFile($fileName, $params['name']);

        if ($this->varienIo->fileExists($fileName) && Zend_Loader::isReadable($fileName)) {
            $oystLog = Oyst_OneClick_Helper_Data::MODULE_NAME . '.log';
            if ($params['name'] !== $oystLog) {
                Mage::getSingleton('core/session')->addError(
                    Mage::helper('oyst_oneclick')->__('Cannot delete "%s" file.', $params['name'])
                );
            } else {
                $this->varienIo->rm($fileName);
                Mage::getSingleton('core/session')->addSuccess(
                    Mage::helper('oyst_oneclick')->__('Successfully deleted "%s".', $oystLog)
                );
            }
        }

        $this->_redirectReferer();
    }

    /**
     * Display error message if cannot find file.
     *
     * @param $filePath
     * @param $name
     */
    private function missingFile($filePath, $name)
    {
        if (!$this->varienIo->fileExists($filePath)) {
            Mage::getSingleton('core/session')->addError(
                Mage::helper('oyst_oneclick')->__('File "%s" does not exist.', $name)
            );
        }
    }
}
