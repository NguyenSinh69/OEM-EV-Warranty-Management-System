<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Claim;
use Exception;
use InvalidArgumentException;

class ClaimsController {
    /**
     * Lấy danh sách claims
     */
    public function index(Request $request, Response $response, array $args): Response {
        try {
            $q = $request->getQueryParams();
            $page = max(1, (int)($q['page'] ?? 1));
            $limit = min(100, (int)($q['limit'] ?? 20));
            $offset = ($page - 1) * $limit;
            
            $filters = [];
            if (!empty($q['vin'])) $filters['vin'] = $q['vin'];
            if (!empty($q['status'])) $filters['status'] = $q['status'];

            $data = Claim::all($filters, $limit, $offset);
            return $this->jsonResponse($response, ['data' => $data]);
        } catch (Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }

    /**
     * Xem chi tiết một claim
     */
    public function show(Request $request, Response $response, array $args): Response {
        try {
            $id = $args['id'];
            $claim = Claim::find($id);
            
            if (!$claim) {
                return $this->errorResponse($response, 'Claim not found', 404);
            }

            return $this->jsonResponse($response, ['data' => $claim]);
        } catch (Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }

    /**
     * Tạo claim mới
     */
    public function create(Request $request, Response $response, array $args): Response {
        try {
            // Debug: capture raw body and parsed body to diagnose JSON parsing issues
            $rawBody = (string)$request->getBody();
            $parsedBody = $request->getParsedBody();

            // If parsed body is null (some servers/clients), try to decode raw JSON body as fallback
            if (is_null($parsedBody) && $rawBody) {
                $decoded = json_decode($rawBody, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $parsedBody = $decoded;
                }
            }

            $data = $parsedBody;
            
            if (empty($data['vin']) || empty($data['customer_id'])) {
                return $this->errorResponse(
                    $response, 
                    'Missing required fields', 
                    422,
                    ['required' => ['vin', 'customer_id']]
                );
            }

            $claim = Claim::create($data);
            return $this->jsonResponse($response, ['data' => $claim], 201);
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($response, $e->getMessage(), 422);
        } catch (Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }

    /**
     * Cập nhật claim
     */
    public function update(Request $request, Response $response, array $args): Response {
        try {
            $id = $args['id'];
            $data = $request->getParsedBody();

            if (!Claim::find($id)) {
                return $this->errorResponse($response, 'Claim not found', 404);
            }

            $claim = Claim::update($id, $data);
            return $this->jsonResponse($response, ['data' => $claim]);
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($response, $e->getMessage(), 422);
        } catch (Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }

    /**
     * Xóa claim
     */
    public function delete(Request $request, Response $response, array $args): Response {
        try {
            $id = $args['id'];
            
            if (!Claim::find($id)) {
                return $this->errorResponse($response, 'Claim not found', 404);
            }

            Claim::delete($id);
            return $response->withStatus(204);
        } catch (Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }

    /**
     * Helper method để trả về JSON response
     */
    private function jsonResponse(Response $response, array $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    /**
     * Helper method để trả về error response
     */
    private function errorResponse(Response $response, string $message, int $status, array $extra = []): Response {
        $error = ['error' => $message] + $extra;
        return $this->jsonResponse($response, $error, $status);
    }
}