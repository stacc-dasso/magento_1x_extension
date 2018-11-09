<?php

/**
 * Class Stacc_Recommender_Model_Logdispatcher
 */
class Stacc_Recommender_Model_Logdispatcher
{
    /**
     * @var
     */
    private $apiClient;
    /**
     * @var
     */
    private $logFile;

    /**
     * @var bool
     */
    private $response = false;

    /**
     * Sends logs to STACC API
     *
     * @return $this
     */
    public function sendLogs()
    {
        try {

            $this->logFile = Mage::getBaseDir('log') . '/stacc_unsent.log';
            $this->apiClient = Mage::helper('recommender/apiclient');
            $io = new Varien_Io_File();
            $io->open(array('path' => Mage::getBaseDir('log')));
            $io->streamOpen($this->logFile, "r");
            $logs = $this->retrieveLogs($io);

            $this->processLogs($io, $logs);

            $io->streamClose();

            return $this;
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Model/RemoteLogDispatcher->sendLogs() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
        }
        return $this;
    }

    /**
     * Retrieves logs from stacc_unsent.log
     *
     * @return array
     */
    public function retrieveLogs($io)
    {
        $logs = array();

        try {

            if ($io->fileExists($this->logFile)) {

                while (($rawLog = $io->streamRead()) !== false) {
                    $logs[] = json_decode(trim($rawLog));
                }

                $logs = array_merge([[
                    "channel" => Stacc_Recommender_Helper_Logger::CHANNEL,
                    "level" => "INFO",
                    "msg" => "Sending " . (count($logs) + 2) . " logs",
                    "timestamp" => time(),
                    "context" => ["size" => (count($logs) + 2)],
                    "extension_version" => Mage::helper("recommender/environment")->getVersion()
                ]], $logs);

            } else {
                Mage::helper('recommender/logger')->logError("Can't open the file", array($this->logFile));
            }
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Model/RemoteLogDispatcher->retrieveLogs() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
        }

        return $logs;
    }

    /**
     * Processes logs to batches for sending and sends them via apiClient
     *
     * @param $io
     * @param $logs
     */
    private function processLogs($io, $logs)
    {
        try {
            $batchSize = 250;
            $errors = 0;
            $sendSliceSize = 0;
            $logsSize = count($logs) + 1; // Increase log count by added starting and ending message
            if ($logsSize > 0) {

                for ($i = 0; $i < $logsSize; $i += $batchSize) {
                    if ($errors > 0) {
                        Mage::helper('recommender/logger')->logError("Failed to send the logs", array("lastBatch" => $sendSliceSize . "/" . $logsSize));
                        $this->setResponse(false);
                        break;
                    }
                    $sendSlice = array_slice($logs, $i, $batchSize);
                    $sendSliceSize = count($sendSlice);
                    if (count($sendSlice) < $batchSize) {
                        $sendSlice[] = array(
                            "channel" => Stacc_Recommender_Helper_Logger::CHANNEL,
                            "level" => "INFO",
                            "msg" => "Finished sending logs " . ($sendSliceSize + 1) . "/" . ($logsSize),
                            "timestamp" => time(),
                            "context" => ["size" => $sendSliceSize + 1],
                            "extension_version" => Mage::helper("recommender/environment")->getVersion()
                        );
                    }
                    $request = $this->apiClient->sendLogs($sendSlice);
                    $this->setResponse($request == "{}");
                    if (!$this->getResponse()) {
                        $errors++;

                        Mage::helper('recommender/logger')->logError("No. " . $errors, array($this->logFile));
                    }
                }

                if ($this->getResponse()) {
                    // Remove the log file to prevent duplicating logs
                    $io->rm($this->logFile);
                }
            }
        } catch (Exception $exception) {
            Mage::helper('recommender/logger')->logCritical("Model/RemoteLogDispatcher->processLogs() Exception: ", array(get_class($exception), $exception->getMessage(), $exception->getCode()));
        }
    }

    /**
     * Returns response got from sending data
     *
     * @return bool
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param $response
     */
    private function setResponse($response)
    {
        $this->response = $response;
    }
}
