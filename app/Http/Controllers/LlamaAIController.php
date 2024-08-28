<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Models\Estudiante;
use App\Models\ChatLog;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Crypt;

class LlamaAIController extends Controller
{
    protected $llamaClient;

    public function __construct()
    {
        $this->llamaClient = new Client([
            'base_uri' => 'https://api.llama-api.com/',
            'headers'  => [
                'Authorization' => 'Bearer ' . "LA-d59d6593e63c4e588573023502ce524e010552be957042b19fd1bad801d714dd",
                'Content-Type'  => 'application/json',
            ],
        ]);
    }

    public function processDocumentAndQuestion(Request $request)
    {
        try {
           // $file = $request->file('document');
            $question = $request->input('question');
            $language = $request->input('language', 'es');

            // if (!$file || !$question) {
            //     return response()->json(['error' => 'Document and question are required'], 400);
            // }

            $filePath = public_path('images/archivo.txt');
            // Leer el contenido del archivo .txt
            $fileContent = file_get_contents($filePath);
        
            // Convertir el contenido a UTF-8
            $fileContent = mb_convert_encoding($fileContent, 'UTF-8', 'auto');
        
            // Limpiar caracteres que puedan causar problemas
            $fileContent = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $fileContent);
            $apiRequestJson = $this->prepareRequest($fileContent, $question, $language);

            $response = $this->llamaClient->post('chat/completions', [
                'json' => $apiRequestJson,
            ]);

            $responseJson = json_decode($response->getBody()->getContents(), true);
            //return $responseJson['choices'][0]['message']['function_call'];
            if (isset($responseJson['choices'][0]['message']['content'])) {
                $assistantResponse = $responseJson['choices'][0]['message']['content'];
            } else {
                $assistantResponse = "No se encontró el contenido esperado.";
            }

            return response()->json([
                'response' => $assistantResponse,
            ]);
        } catch (RequestException $e) {
            return response()->json([
                'error' => 'Unable to process request.',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function readDocx($filePath)
    {
        // Use PHPWord or any other library for reading .docx files
        $content = '';
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $content .= $element->getText() . "\n";
                }
            }
        }
        return $content;
    }

    private function prepareRequest($fileContent, $question, $language)
    {
        return [
            "model" => "llama-13b-chat",
            // "functions" => [
            //     [
            //         "name" => $this->get_registration_link(),
            //         "description" => "Provide the registration link when requested",
            //         "parameters" => [
            //             "type" => "object",
            //             "properties" => [
            //                 "link" => [
            //                     "type" => "string",
            //                     "description" => "The registration link"
            //                 ]
            //             ],
            //             "required" => ["link"]
            //         ]
            //     ]
            // ],
            "messages" => [
                [
                    "role" => "user",
                    "content" => "Por favor, responde en {$language}. De este documento: '{$fileContent}', {$question}"
                ]
            ],
            "max_tokens" => 250,
            "stream" => false
        ];
    }

    private function get_registration_link()
    {
        return 'https://www.google.com';
    }

}
