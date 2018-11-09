<?php

/**
 * Class Stacc_Recommender_Helper_Logger
 */
class Stacc_Recommender_Helper_Logger extends Mage_Core_Helper_Abstract
{

    /**
     * Define constant channel for the extension
     */
    const CHANNEL = "MAGE_EXTENSION";
    /**
     * @var string
     */
    private $logFile;

    /**
     * Stacc_Recommender_Helper_Logger constructor.
     *
     * @param $logPath
     */
    public function __construct($logPath = null)
    {
        if (is_null($logPath)) {
            try {
                $this->logFile = Mage::getBaseDir("log") . "/stacc_unsent.log";
            } catch (Exception $exception) {
                $this->logFile = "";
            }
        } else {
            $this->logFile = $logPath;
        }
    }

    /**
     * Returns file path for log file
     *
     * @return string
     */
    public function getLogFile()
    {
        return $this->logFile;
    }


    /**
     * Method for logging critical errors
     *
     * @param $message
     * @param array $context
     */
    public function logCritical($message, $context = array())
    {
        try {
            $this->log(
                $message,
                Stacc_Recommender_Helper_Logger::CHANNEL,
                "CRITICAL",
                time(),
                $context
            );
        } catch (Exception $exception) {
            Mage::log("Stacc Logger Exception", null, "stacc_exception.log", true);
            Mage::log(json_encode($context), null, "stacc_exception.log", true);
        }
    }

    /**
     * Method for debugging errors
     *
     * @param $message
     * @param array $context
     */
    public function logDebug($message, $context = array())
    {
        try {
            $this->log(
                $message,
                Stacc_Recommender_Helper_Logger::CHANNEL,
                "DEBUG",
                time(),
                $context
            );
        } catch (Exception $exception) {
            Mage::log("Stacc Logger Exception", null, "stacc_exception.log", true);
            Mage::log(json_encode($context), null, "stacc_exception.log", true);
        }
    }

    /**
     * Method for logging basic errors
     *
     * @param $message
     * @param array $context
     */
    public function logError($message, $context = array())
    {
        try {
            $this->log(
                $message,
                Stacc_Recommender_Helper_Logger::CHANNEL,
                "ERROR",
                time(),
                $context
            );
        } catch (Exception $exception) {
            Mage::log("Stacc Logger Exception", null, "stacc_exception.log", true);
            Mage::log(json_encode($context), null, "stacc_exception.log", true);
        }
    }

    /**
     * Method for info logging
     *
     * @param $message
     * @param array $context
     */
    public function logInfo($message, $context = array())
    {
        try {
            $this->log(
                $message,
                Stacc_Recommender_Helper_Logger::CHANNEL,
                "INFO",
                time(),
                $context
            );
        } catch (Exception $exception) {
            Mage::log("Stacc Logger Exception", null, "stacc_exception.log", true);
            Mage::log(json_encode($context), null, "stacc_exception.log", true);
        }
    }

    /**
     * Method for logging a notice
     *
     * @param $message
     * @param array $context
     */
    public function logNotice($message, $context = array())
    {
        try {
            $this->log(
                $message,
                Stacc_Recommender_Helper_Logger::CHANNEL,
                "NOTICE",
                time(),
                $context
            );
        } catch (Exception $exception) {
            Mage::log("Stacc Logger Exception", null, "stacc_exception.log", true);
            Mage::log(json_encode($context), null, "stacc_exception.log", true);
        }
    }

    /**
     * Log method that handles the data related to different types of errors
     *
     * @param $message
     * @param $channel
     * @param $level
     * @param $timestamp
     * @param $context
     */
    private function log($message, $channel, $level, $timestamp, $context)
    {
        $content = json_encode([
            "channel" => $channel,
            "level" => $level,
            "msg" => $message,
            "timestamp" => $timestamp,
            "context" => $context,
            "extension_version" => Mage::helper("recommender/environment")->getVersion()
        ]);

        try {

            $io = new Varien_Io_File();
            $io->open(array("path" => Mage::getBaseDir("log")));
            $io->streamOpen($this->getLogFile(), "a+");
            $io->streamWrite($content . "\n");
            $io->streamClose();
        } catch (Exception $exception) {
            Mage::log("Stacc Logger Exception", null, "stacc_exception.log", true);
            Mage::log($content, null, "stacc_exception.log", true);
        }
    }
}
