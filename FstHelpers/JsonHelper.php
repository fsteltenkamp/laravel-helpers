<?php

namespace App\Helpers\FstHelpers;

class JsonHelper
{
    /**
     * Prints a JSON response.
     *
     * @param string $jsonString The JSON string to be printed.
     * @return void
     */
    public static function printJsonResponse($jsonString)
    {
        header('Content-Type: application/json');
        echo $jsonString;
    }

    /**
     * Generates a JSON response string based on the evaluation result.
     *
     * @param mixed $eval Data to be evaluated.
     * @param string $successResponse The success response message.
     * @param string $failResponse The fail response message.
     * @param bool $allowCustomResponse (optional) Flag to allow custom response message. Default is false.
     * @param bool $returnAsDataOnSuccess (optional) Flag to return the evaluation result as data on success. Default is false.
     * @return string The JSON response string.
     */
    public static function jsonResponseString($eval, $successResponse, $failResponse, $allowCustomResponse = false, $returnAsDataOnSuccess = false)
    {
        if ($eval === false || ($allowCustomResponse && is_string($eval))) {
            if ($allowCustomResponse && is_string($eval)) {
                return json_encode(array('success' => false, 'message' => $eval));
            } else {
                return json_encode(array('success' => false, 'message' => $failResponse));
            }
        } else {
            if ($returnAsDataOnSuccess) {
                return json_encode(['success' => true, 'data' => $eval]);
            } else {
                return json_encode(array('success' => true, 'message' => $successResponse));
            }
        }
    }

    /**
     * Generate a simple JSON response.
     *
     * @param string $message The response message.
     * @param bool $success The success status of the response. Default is false.
     * @param array $data The data to be included in the response. Default is an empty array.
     * @param int $httpcode The HTTP status code of the response. Default is 200.
     * @return \Illuminate\Http\JsonResponse The JSON response.
     */
    public static function simpleJsonResponse($message, $success = false, $data = [], $httpcode = 200)
    {
        return response()->json(['success' => $success, 'message' => $message, 'data' => $data], $httpcode);
    }

    /**
     * Returns a JSON response for access denied.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function accessDeniedJsonResponse()
    {
        return self::simpleJsonResponse('Access denied', false, [], 403);
    }

    /**
     * Returns a JSON response for not found.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function notFoundJsonResponse()
    {
        return self::simpleJsonResponse('The resource you are trying to access could not be found.', false, [], 404);
    }

    public static function success($message)
    {
        return self::simpleJsonResponse($message, true);
    }

    public static function fail($message)
    {
        return self::simpleJsonResponse($message, false);
    }
}
