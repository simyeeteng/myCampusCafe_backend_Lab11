<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/security.php';

$app = AppFactory::create();
$app->setBasePath('/mycampus-cafe-slim-api/public');

// Middleware: parse JSON/form request body.
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// CORS Middleware - ONLY ONE OPTIONS ROUTE (NO specific /api/login OPTIONS)
$app->options('/{routes:.+}', function (Request $request, Response $response) {
    return $response;
});

$app->add(function (Request $request, RequestHandler $handler): Response {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

// Helper function for JSON responses
function jsonResponse(Response $response, $data, int $status = 200): Response {
    $payload = json_encode($data, JSON_PRETTY_PRINT);
    $response->getBody()->write($payload);
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($status);
}

// ============ LOGIN ROUTE (PUBLIC - NO OPTIONS ROUTE NEEDED) ============
$app->post('/api/login', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    
    if (empty($data['username']) || empty($data['password'])) {
        return jsonResponse($response, [
            'status' => 'fail',
            'message' => 'Username and password are required'
        ], 400);
    }
    
    try {
        $db = (new DB())->connect();
        $stmt = $db->prepare(
            'SELECT staff_id, username, bcrypt_password, role
             FROM staff
             WHERE username = :username'
        );
        $stmt->execute([':username' => $data['username']]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$staff || !bcryptVerify($data['password'], $staff['bcrypt_password'])) {
            return jsonResponse($response, [
                'status' => 'fail',
                'message' => 'Invalid username or password'
            ], 401);
        }
        
        $token = createJwtToken($staff);
        
        return jsonResponse($response, [
            'status' => 'success',
            'token' => $token,
            'staff' => [
                'staff_id' => $staff['staff_id'],
                'username' => $staff['username'],
                'role' => $staff['role']
            ]
        ], 200);
        
    } catch (PDOException $e) {
        return jsonResponse($response, [
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

// ============ JWT TOKEN VALIDATION MIDDLEWARE ============
$jwtMiddleware = function (Request $request, RequestHandler $handler): Response {
    $authHeader = $request->getHeaderLine('Authorization');
    
    if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $response = new \Slim\Psr7\Response();
        return jsonResponse($response, [
            'status' => 'unauthorized',
            'message' => 'Bearer token is required'
        ], 401);
    }
    
    try {
        $token = $matches[1];
        $decoded = JWT::decode($token, new Key(JWT_SECRET, JWT_ALG));
        $request = $request->withAttribute('staff', $decoded);
        return $handler->handle($request);
    } catch (Exception $e) {
        $response = new \Slim\Psr7\Response();
        return jsonResponse($response, [
            'status' => 'unauthorized',
            'message' => 'Invalid or expired token'
        ], 401);
    }
};

// ============ PUBLIC ROUTES (No token required) ============

// GET: retrieve all menu items.
$app->get('/api/menu', function (Request $request, Response $response) {
    try {
        $db = (new DB())->connect();
        $stmt = $db->query('SELECT * FROM menu ORDER BY menu_id DESC');
        return jsonResponse($response, $stmt->fetchAll());
    } catch (PDOException $e) {
        return jsonResponse($response, ['status' => 'error', 'message' => $e->getMessage()], 500);
    }
});

// GET: retrieve one menu item.
$app->get('/api/menu/{id}', function (Request $request, Response $response, array $args) {
    try {
        $db = (new DB())->connect();
        $stmt = $db->prepare('SELECT * FROM menu WHERE menu_id = :id');
        $stmt->bindValue(':id', (int)$args['id'], PDO::PARAM_INT);
        $stmt->execute();
        $item = $stmt->fetch();
        if (!$item) {
            return jsonResponse($response, ['status' => 'not_found'], 404);
        }
        return jsonResponse($response, $item);
    } catch (PDOException $e) {
        return jsonResponse($response, ['status' => 'error', 'message' => $e->getMessage()], 500);
    }
});

// ============ PROTECTED ROUTES (Token required) ============

// POST: create new menu item (PROTECTED)
$app->post('/api/menu', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    
    if (empty($data['menu_name']) || empty($data['category']) || empty($data['price']) || empty($data['availability'])) {
        return jsonResponse($response, ['status' => 'fail', 'message' => 'Incomplete input'], 400);
    }
    
    try {
        $db = (new DB())->connect();
        $sql = 'INSERT INTO menu (menu_name, category, price, availability)
                VALUES (:menu_name, :category, :price, :availability)';
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':menu_name' => $data['menu_name'],
            ':category' => $data['category'],
            ':price' => $data['price'],
            ':availability' => $data['availability']
        ]);
        
        return jsonResponse($response, [
            'status' => 'success',
            'menu_id' => $db->lastInsertId()
        ], 201);
        
    } catch (PDOException $e) {
        return jsonResponse($response, ['status' => 'error', 'message' => $e->getMessage()], 500);
    }
})->add($jwtMiddleware);

// PUT: update existing menu item (PROTECTED)
$app->put('/api/menu/{id}', function (Request $request, Response $response, array $args) {
    $data = $request->getParsedBody();
    
    try {
        $db = (new DB())->connect();
        $check = $db->prepare('SELECT menu_id FROM menu WHERE menu_id = :id');
        $check->execute([':id' => (int)$args['id']]);
        
        if (!$check->fetch()) {
            return jsonResponse($response, ['status' => 'not_found'], 404);
        }
        
        $sql = 'UPDATE menu 
                SET menu_name = :menu_name, 
                    category = :category, 
                    price = :price, 
                    availability = :availability 
                WHERE menu_id = :id';
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':menu_name' => $data['menu_name'],
            ':category' => $data['category'],
            ':price' => $data['price'],
            ':availability' => $data['availability'],
            ':id' => (int)$args['id']
        ]);
        
        return jsonResponse($response, ['status' => 'success', 'message' => 'Menu updated']);
        
    } catch (PDOException $e) {
        return jsonResponse($response, ['status' => 'error', 'message' => $e->getMessage()], 500);
    }
})->add($jwtMiddleware);

// DELETE: delete menu item (PROTECTED)
$app->delete('/api/menu/{id}', function (Request $request, Response $response, array $args) {
    try {
        $db = (new DB())->connect();
        $stmt = $db->prepare('DELETE FROM menu WHERE menu_id = :id');
        $stmt->execute([':id' => (int)$args['id']]);
        
        if ($stmt->rowCount() === 0) {
            return jsonResponse($response, ['status' => 'not_found'], 404);
        }
        
        return jsonResponse($response, ['status' => 'success', 'message' => 'Menu deleted']);
        
    } catch (PDOException $e) {
        return jsonResponse($response, ['status' => 'error', 'message' => $e->getMessage()], 500);
    }
})->add($jwtMiddleware);

$app->run();
?>