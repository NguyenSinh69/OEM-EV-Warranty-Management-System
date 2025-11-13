<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Claim;
use App\Models\Attachment;
use App\Database\Database;
use Exception;
use InvalidArgumentException;

class ClaimsController {

    /** ADMIN/LIST CHUNG: GET /api/claims */
    public function index(Request $request, Response $response, array $args): Response {
        try {
            $q = $request->getQueryParams();
            $page = max(1, (int)($q['page'] ?? 1));
            $limit = min(100, (int)($q['limit'] ?? 20));
            $offset = ($page - 1) * $limit;

            $filters = [];
            if (!empty($q['vin'])) $filters['vin'] = $q['vin'];
            if (!empty($q['status'])) $filters['status'] = $q['status'];
            if (!empty($q['customer_id'])) $filters['customer_id'] = (int)$q['customer_id'];

            $data = Claim::all($filters, $limit, $offset);
            return $this->jsonResponse($response, ['data' => $data]);
        } catch (Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }

    /** ADMIN/DETAIL: GET /api/claims/{id} */
    public function show(Request $request, Response $response, array $args): Response {
        try {
            $id = $args['id'];
            $q  = $request->getQueryParams();

            $claim = Claim::find($id);
            if (!$claim) {
                return $this->errorResponse($response, 'Claim not found', 404);
            }

            // Nếu là truy cập từ Customer (có query customer_id) → kiểm quyền
            if (!empty($q['customer_id']) && (int)$claim['customer_id'] !== (int)$q['customer_id']) {
                return $this->errorResponse($response, 'Forbidden', 403);
            }

            $claim['attachments'] = Attachment::listByClaim($id);
            return $this->jsonResponse($response, ['data' => $claim]);
        } catch (Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }

    /** ADMIN/CREATE: POST /api/claims */
    public function create(Request $request, Response $response, array $args): Response {
        try {
            $rawBody = (string)$request->getBody();
            $parsedBody = $request->getParsedBody();
            if (is_null($parsedBody) && $rawBody) {
                $decoded = json_decode($rawBody, true);
                if (json_last_error() === JSON_ERROR_NONE) $parsedBody = $decoded;
            }
            $data = $parsedBody;

            if (empty($data['vin']) || empty($data['customer_id'])) {
                return $this->errorResponse($response, 'Missing required fields', 422, [
                    'required' => ['vin', 'customer_id']
                ]);
            }

            $claim = Claim::create($data); // tạo với status mặc định (PENDING)
            return $this->jsonResponse($response, ['data' => $claim], 201);
        } catch (InvalidArgumentException $e) {
            return $this->errorResponse($response, $e->getMessage(), 422);
        } catch (Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }

    /** ADMIN/UPDATE: PUT /api/claims/{id} */
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

    /** ADMIN/DELETE: DELETE /api/claims/{id} */
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

    /** CUSTOMER/LIST: GET /api/customer/claims?customer_id=&status=&page=&limit= */
    public function listByCustomer(Request $request, Response $response, array $args): Response {
        try {
            $q = $request->getQueryParams();
            if (empty($q['customer_id'])) {
                return $this->errorResponse($response, 'customer_id is required', 400);
            }
            $page  = max(1, (int)($q['page'] ?? 1));
            $limit = min(100, max(1, (int)($q['limit'] ?? 10)));
            $offset = ($page - 1) * $limit;

            $filters = ['customer_id' => (int)$q['customer_id']];
            if (!empty($q['status'])) $filters['status'] = $q['status'];

            $items = Claim::all($filters, $limit, $offset);

            $db = Database::getConnection();
            $where = 'WHERE customer_id = :cid' . (!empty($q['status']) ? ' AND status = :st' : '');
            $count = $db->prepare("SELECT COUNT(*) FROM claims $where");
            $count->bindValue(':cid', (int)$q['customer_id'], \PDO::PARAM_INT);
            if (!empty($q['status'])) $count->bindValue(':st', $q['status']);
            $count->execute();
            $total = (int)$count->fetchColumn();

            return $this->jsonResponse($response, [
                'items' => $items,
                'page'  => $page,
                'limit' => $limit,
                'total' => $total,
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }

    /** CUSTOMER/DETAIL: GET /api/customer/claims/{id}?customer_id= */
    public function customerDetail(Request $request, Response $response, array $args): Response {
        try {
            $id = $args['id'];
            $q  = $request->getQueryParams();
            $customerId = isset($q['customer_id']) ? (int)$q['customer_id'] : 0;
            if (!$customerId) {
                return $this->errorResponse($response, 'customer_id is required', 400);
            }

            $claim = Claim::find($id);
            if (!$claim) return $this->errorResponse($response, 'Claim not found', 404);
            if ((int)$claim['customer_id'] !== $customerId) return $this->errorResponse($response, 'Forbidden', 403);

            $claim['attachments'] = Attachment::listByClaim($id);
            return $this->jsonResponse($response, ['data' => $claim]);
        } catch (Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }

    /** CUSTOMER/UPLOAD: POST /api/customer/claims/{id}/attachments (form-data) */
    public function uploadAttachments(Request $request, Response $response, array $args): Response {
        try {
            $id = $args['id'];
            $post = $request->getParsedBody() ?? [];
            $customerId = isset($post['customer_id']) ? (int)$post['customer_id'] : (int)($_POST['customer_id'] ?? 0);
            if (!$customerId) return $this->errorResponse($response, 'customer_id is required', 400);

            $claim = Claim::find($id);
            if (!$claim) return $this->errorResponse($response, 'Claim not found', 404);
            if ((int)$claim['customer_id'] !== $customerId) return $this->errorResponse($response, 'Forbidden', 403);

            if (!isset($_FILES['files'])) {
                return $this->errorResponse($response, 'No files uploaded (expect files[])', 400);
            }

            $base = __DIR__ . '/../../uploads';
            $dest = $base . '/claims/' . $id;
            if (!is_dir($dest)) @mkdir($dest, 0777, true);

            $saved = [];
            $f = $_FILES['files'];
            $n = is_array($f['name']) ? count($f['name']) : 0;

            for ($i = 0; $i < $n; $i++) {
                if ($f['error'][$i] !== UPLOAD_ERR_OK) continue;

                $tmp  = $f['tmp_name'][$i];
                $name = basename($f['name'][$i]);
                $mime = $f['type'][$i] ?? null;
                $size = (int)($f['size'][$i] ?? 0);

                $target = $dest . '/' . uniqid() . '_' . $name;
                if (!move_uploaded_file($tmp, $target)) continue;

                $rel = 'uploads/claims/' . $id . '/' . basename($target);
                $att = Attachment::create([
                    'claim_id' => $id,
                    'filename' => $name,
                    'path'     => $rel,
                    'mimetype' => $mime,
                    'size'     => $size,
                ]);
                $saved[] = $att;
            }

            return $this->jsonResponse($response, ['saved' => $saved], 201);
        } catch (Exception $e) {
            return $this->errorResponse($response, $e->getMessage(), 500);
        }
    }

    /** Helpers */
    private function jsonResponse(Response $response, array $data, int $status = 200): Response {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
    private function errorResponse(Response $response, string $message, int $status, array $extra = []): Response {
        $error = ['error' => $message] + $extra;
        return $this->jsonResponse($response, $error, $status);
    }
}
