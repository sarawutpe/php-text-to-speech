<?php
// ini_set('error_reporting', E_ALL);
// ini_set('display_errors', 'On');
// ini_set('memory_limit', '256M');

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Origin: *');

require __DIR__ . '/vendor/autoload.php';

use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;
use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;

class api_v1
{
    function __construct()
    {
        $this->index();
    }

    public function index()
    {
        $this->getTextToSpeech();
    }

    private function getVoiceFromCache($text)
    {
        if (!$text) return '';
        $path = "files/com_voice/" . md5($text) . ".mp3";
        if (file_exists($path)) {
            return $path;
        }
        return '';
    }

    private function getTextToSpeech()
    {
        try {
            // Headers
            $text = trim($_POST['text']);
            $text = preg_replace('/\s+/', '', $text);

            if (empty($text)) {
                $responseArray = array(
                    "status" => "error",
                    "message" => 'Text is required.'
                );
                echo json_encode($responseArray);
                exit();
            }

            // Get audio from cache
            $audio_cache = $this->getVoiceFromCache($text);
            if (!empty($audio_cache)) {
                $responseArray = array(
                    "status" => "success",
                    "voice_url" => $audio_cache
                );
                echo json_encode($responseArray);
                exit();
            }

            // Set the path to your service account credentials JSON file
            putenv('GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . '/credentials/service_account_credentials.json');

            $textToSpeechClient = new TextToSpeechClient();

            // Set the text input to be synthesized
            $input = new SynthesisInput();
            $input->setText($text);

            // Build the voice request
            $voice = new VoiceSelectionParams();
            $voice->setLanguageCode('th-TH');
            $voice->setName('th-TH-Standard-A');
            $audioConfig = new AudioConfig();
            $audioConfig->setAudioEncoding(AudioEncoding::MP3);

            // Get audio content
            $response = $textToSpeechClient->synthesizeSpeech($input, $voice, $audioConfig);
            $audioContent = $response->getAudioContent();
            $audioFilePath = 'files/com_voice/' . md5($text) . '.mp3';
            file_put_contents($audioFilePath, $audioContent);

            // Response
            $responseArray = array(
                "status" => "success",
                'message' => 'Audio content generated successfully.',
                "voice_url" => $audioFilePath
            );
            echo json_encode($responseArray);
            exit();
        } catch (Exception $e) {
            http_response_code(400);
            $responseArray = array("status" => "error", "message" => $e->getMessage());
            echo json_encode($responseArray);
            exit();
        } finally {
            $textToSpeechClient->close();
            $this->logger("Handle", "Request");
        }
    }

    private function logger($message, $prefix = '')
    {
        if (empty($message)) {
            return;
        }

        $logFile = 'files/com_voice/logger.txt';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $prefix: $message\n";

        try {
            // Check if the file exists, create it if it doesn't
            if (!file_exists($logFile)) {
                $fileHandle = fopen($logFile, 'w');
                if (!$fileHandle) {
                    throw new Exception("Unable to create log file '$logFile'");
                }
                $initMessage = "[$timestamp] File created and initialized.\n";
                fwrite($fileHandle, $initMessage);
                fclose($fileHandle);
            }

            // Append the log message to the file
            $fileHandle = fopen($logFile, 'a');
            if (!$fileHandle) {
                throw new Exception("Unable to open log file '$logFile' for appending");
            }
            fwrite($fileHandle, $logMessage);
            fclose($fileHandle);
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage() . "\n";
        }
    }
}

$api = new api_v1();
