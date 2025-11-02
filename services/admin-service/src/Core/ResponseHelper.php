<?php
namespace Core;

class ResponseHelper {
    /**
     * Primary JSON responder. Prints JSON and terminates request.
     *
     * @param mixed $data
     * @param int $status HTTP status code
     */
    public static function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Backwards-compatible alias used in some controllers.
     * Keeps the same signature as older code (jsonResponse).
     */
    public static function jsonResponse($data, $status = 200) {
        // delegate to the main json() implementation
        self::json($data, $status);
    }
}
