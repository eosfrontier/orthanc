<?php
/**
 * Focused test suite for the /v2/shuttlebay/ model and HTTP REST endpoints.
 *
 * Run with: php tests/ShuttlebayTest.php
 *
 * Uses the configured MySQL database (includes/classes/Database.php).
 * Model tests run inside a rolled-back transaction; HTTP tests create only
 * isolated test records and a temporary API token, then clean them up in
 * a finally block without modifying any existing shuttle rows.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/classes/Database.php';
require_once __DIR__ . '/../includes/classes/Shuttlebay.php';

$passed = 0;
$failed = 0;

function test_assert(bool $condition, string $message): void
{
	if (! $condition) {
		throw new AssertionError($message);
	}
}

function test_eq($expected, $actual, string $label): void
{
	if ($expected !== $actual) {
		throw new AssertionError(
			$label . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true)
		);
	}
}

function run_case(string $name, callable $fn): void
{
	global $passed, $failed;
	try {
		$fn();
		$passed++;
		echo "[PASS] {$name}\n";
	} catch (Throwable $e) {
		$failed++;
		echo "[FAIL] {$name}: {$e->getMessage()}\n";
	}
}

function http_request(string $url, string $method, array $headers = [], ?string $body = null): array
{
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);

	$headerLines = ['Expect:'];
	foreach ($headers as $k => $v) {
		$headerLines[] = $k . ': ' . $v;
	}
	if ($body !== null) {
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
	}
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headerLines);

	$raw = curl_exec($ch);
	if ($raw === false) {
		$err = curl_error($ch);
		throw new RuntimeException("cURL error for {$method} {$url}: {$err}");
	}

	$statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$rawHeaders = substr($raw, 0, $headerSize);
	$respBody   = substr($raw, $headerSize);

	$parsedHeaders = [];
	foreach (explode("\r\n", $rawHeaders) as $line) {
		$pos = strpos($line, ':');
		if ($pos !== false) {
			$key                 = strtolower(trim(substr($line, 0, $pos)));
			$val                 = trim(substr($line, $pos + 1));
			$parsedHeaders[$key] = $val;
		}
	}

	$json = null;
	if ($respBody !== '') {
		$json = json_decode($respBody, true);
	}

	return [
		'status'  => $statusCode,
		'headers' => $parsedHeaders,
		'body'    => $respBody,
		'json'    => $json,
	];
}

function find_free_port(): int
{
	$sock = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
	if ($sock === false) {
		throw new RuntimeException("Could not allocate local port: {$errstr}");
	}
	$name = stream_socket_get_name($sock, false);
	fclose($sock);
	$parts = explode(':', (string) $name);
	return (int) end($parts);
}

$pdo            = Database::$conn;
$initialMaxShut = (int) $pdo->query('SELECT COALESCE(MAX(id), 0) FROM esb_shuttles')->fetchColumn();
$initialCount   = (int) $pdo->query('SELECT COUNT(*) FROM esb_shuttles WHERE status != ' . Shuttlebay::STATUS_DELETED)->fetchColumn();
$initialMaxTok  = (int) $pdo->query('SELECT COALESCE(MAX(token_id), 0) FROM eos_tokens')->fetchColumn();

// ---------------------------------------------------------------------------
// 1. Model unit tests (executed inside a rolled-back PDO transaction)
// ---------------------------------------------------------------------------
echo "== Shuttlebay model tests ==\n";

$pdo->beginTransaction();
try {
	$sb = new Shuttlebay($pdo);

	run_case('Model: createShuttle with minimal required fields uses defaults', function () use ($sb) {
		$row = $sb->createShuttle([
			'name'          => 'ModelMinimal',
			'serial_number' => 'MM-001',
			'class'         => 'Runner-Class',
		]);
		test_assert((int) $row['id'] > 0, 'Expected positive auto-increment id');
		test_eq('ModelMinimal', $row['name'], 'name');
		test_eq('MM-001', $row['serial_number'], 'serial_number');
		test_eq('Runner-Class', $row['class'], 'class');
		test_eq(3, (int) $row['class_id'], 'class_id');
		test_eq('Fast Supply Carrier', $row['type'], 'type');
		test_eq(2, (int) $row['capacity'], 'capacity');
		test_eq('Bastion', $row['base'], 'default base');
		test_eq('Fully operational', $row['state'], 'default state');
		test_eq(1, (int) $row['operable'], 'default operable');
		test_eq(0, (int) $row['status'], 'default status');
		test_eq('Ready', $row['status_name'], 'default status_name');
		test_eq(null, $row['assigned_to_id'], 'default assigned_to_id');
	});

	run_case('Model: createShuttle rejects serial_number longer than 10 chars', function () use ($sb) {
		$threw = false;
		try {
			$sb->createShuttle([
				'name'          => 'TooLongSerial',
				'serial_number' => 'LONG-123456',
				'class'         => 'Runner-Class',
			]);
		} catch (InvalidArgumentException $e) {
			$threw = true;
			test_assert(strpos($e->getMessage(), '10') !== false, 'Expected max length 10 message');
		}
		test_assert($threw, 'Expected InvalidArgumentException');
	});

	run_case('Model: createShuttle handles status 0 / "0" and operable "0"', function () use ($sb) {
		$row = $sb->createShuttle([
			'name'          => 'FalsyZero',
			'serial_number' => 'FZ-001',
			'class'         => 'Elmaris',
			'status'        => '0',
			'operable'      => '0',
		]);
		test_eq(0, (int) $row['status'], 'status 0');
		test_eq('Ready', $row['status_name'], 'status_name');
		test_eq(0, (int) $row['operable'], 'operable 0');
	});

	run_case('Model: getShuttle neutralizes non-numeric / SQL injection input', function () use ($sb) {
		$rows = $sb->getShuttle('1 OR 1=1');
		test_eq([], $rows, 'non-numeric id returns empty');
	});

	run_case('Model: deleteShuttle sets status to -999 (soft delete) and hides from queries', function () use ($sb, $pdo) {
		$created = $sb->createShuttle([
			'name'          => 'SoftDelModel',
			'serial_number' => 'SD-001',
			'class'         => 'Runner-Class',
		]);
		$id = (int) $created['id'];
		test_eq(true, $sb->deleteShuttle($id), 'first delete returns true');
		test_eq(false, $sb->deleteShuttle($id), 'second delete returns false');
		test_eq([], $sb->getShuttle($id), 'getShuttle excludes soft-deleted');
		test_eq(null, $sb->getShuttleRecord($id), 'getShuttleRecord excludes soft-deleted');

		$rawStatus = $pdo->query("SELECT status FROM esb_shuttles WHERE id = {$id}")->fetchColumn();
		test_eq(-999, (int) $rawStatus, 'row preserved in DB with status -999');
	});
} finally {
	if ($pdo->inTransaction()) {
		$pdo->rollBack();
	}
}

// ---------------------------------------------------------------------------
// 2. End-to-end HTTP tests via PHP built-in web server
// ---------------------------------------------------------------------------
echo "\n== Shuttlebay HTTP endpoint tests ==\n";

$repoRoot   = realpath(__DIR__ . '/..');
$routerFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'orthanc_shuttlebay_router_' . getmypid() . '.php';
$routerCode = <<<'PHP'
<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (is_string($path) && preg_match('#^(/orthanc)?/v2/shuttlebay(?:/(.*))?$#i', $path, $m)) {
    $rest = isset($m[2]) ? trim($m[2], '/') : '';
    if ($rest !== '' && strpos($rest, '/') === false && $rest !== 'index.php') {
        $_GET['id'] = urldecode($rest);
    }
    require __DIR_PLACEHOLDER__ . '/v2/shuttlebay/index.php';
    return true;
}
http_response_code(404);
header('Content-Type: application/json; charset=UTF-8');
echo json_encode(['error' => 'Not found']);
return true;
PHP;
$routerCode = str_replace('__DIR_PLACEHOLDER__', var_export($repoRoot, true), $routerCode);
file_put_contents($routerFile, $routerCode);

$port       = find_free_port();
$baseUrl    = "http://127.0.0.1:{$port}/v2/shuttlebay/";
$testToken  = 'test_tok_' . bin2hex(random_bytes(10));
$createdIds = [];
$tokenId    = null;
$serverProc = null;

try {
	// Insert a temporary test API token (removed in finally block).
	$tokStmt = $pdo->prepare('INSERT INTO eos_tokens (token, name) VALUES (?, ?)');
	$tokStmt->execute([$testToken, 'test_shuttle']);
	$tokenId = (int) $pdo->lastInsertId();

	$nullSink    = (DIRECTORY_SEPARATOR === '\\') ? 'NUL' : '/dev/null';
	$descriptors = [
		0 => ['file', $nullSink, 'r'],
		1 => ['file', $nullSink, 'w'],
		2 => ['file', $nullSink, 'w'],
	];
	$cmd        = [PHP_BINARY, '-S', "127.0.0.1:{$port}", '-t', $repoRoot, $routerFile];
	$serverProc = proc_open($cmd, $descriptors, $pipes, $repoRoot);
	test_assert(is_resource($serverProc), 'Failed to start PHP built-in test server');

	// Wait until server socket accepts connections.
	$ready = false;
	for ($i = 0; $i < 40; $i++) {
		$fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
		if (is_resource($fp)) {
			fclose($fp);
			$ready = true;
			break;
		}
		usleep(50000);
	}
	test_assert($ready, 'PHP test server did not start in time');

	// CORS preflight checks
	run_case('OPTIONS /v2/shuttlebay/ permits Strati.OS prod origin & required methods/headers', function () use ($baseUrl) {
		$res = http_request($baseUrl, 'OPTIONS', [
			'Origin'                         => 'https://cic.eosfrontier.space',
			'Access-Control-Request-Method'  => 'POST',
			'Access-Control-Request-Headers' => 'content-type, getall, token',
		]);
		test_eq(204, $res['status'], 'HTTP status');
		test_eq('*', $res['headers']['access-control-allow-origin'] ?? '', 'Allow-Origin');
		$methods = strtoupper($res['headers']['access-control-allow-methods'] ?? '');
		foreach (['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'] as $m) {
			test_assert(strpos($methods, $m) !== false, "Allow-Methods missing {$m}: {$methods}");
		}
		$allowHeaders = strtolower($res['headers']['access-control-allow-headers'] ?? '');
		test_assert(strpos($allowHeaders, 'content-type') !== false, 'Allow-Headers missing content-type');
		test_assert(strpos($allowHeaders, 'getall') !== false, 'Allow-Headers missing getall');
		test_assert(strpos($allowHeaders, 'getclasses') !== false, 'Allow-Headers missing getclasses');
		test_assert(strpos($allowHeaders, 'token') !== false, 'Allow-Headers missing token');
	});

	run_case('OPTIONS /v2/shuttlebay/5 permits localhost:4204 dev origin preflight for PUT/DELETE', function () use ($baseUrl) {
		$res = http_request($baseUrl . '5', 'OPTIONS', [
			'Origin'                        => 'http://localhost:4204',
			'Access-Control-Request-Method' => 'DELETE',
		]);
		test_eq(204, $res['status'], 'HTTP status');
		test_eq('*', $res['headers']['access-control-allow-origin'] ?? '', 'Allow-Origin');
	});

	$getHeaders = ['token' => $testToken];

	// Auth checks across all operations (OPTIONS stays unauthenticated above)
	run_case('All methods (GET/POST/PUT/DELETE/PATCH) require valid token header (401 JSON)', function () use ($baseUrl) {
		$body = json_encode(['name' => 'NoAuth', 'serial_number' => 'NA-01', 'class' => 'Runner-Class']);
		foreach (
			[
				['GET', $baseUrl, ['getAll' => 'true'], null],
				['GET', $baseUrl, ['getClasses' => 'true'], null],
				['GET', $baseUrl . '5', [], null],
				['POST', $baseUrl, ['Content-Type' => 'application/json'], $body],
				['PUT', $baseUrl . '5', ['Content-Type' => 'application/json'], $body],
				['DELETE', $baseUrl . '5', [], null],
				['PATCH', $baseUrl, [], null],
			] as $req
		) {
			$noTok = http_request($req[1], $req[0], $req[2], $req[3]);
			test_eq(401, $noTok['status'], "{$req[0]} {$req[1]} without token");
			test_assert(json_decode($noTok['body'], true) !== null, "{$req[0]} 401 returns valid JSON");

			$badHeaders          = $req[2];
			$badHeaders['token'] = 'invalid_token_xyz';
			$badTok              = http_request($req[1], $req[0], $badHeaders, $req[3]);
			test_eq(401, $badTok['status'], "{$req[0]} {$req[1]} with invalid token");
		}
	});

	// Authenticated GET behaviors
	run_case('GET /v2/shuttlebay/ with getAll header succeeds with valid token', function () use ($baseUrl, $initialCount, $getHeaders) {
		$res = http_request($baseUrl, 'GET', $getHeaders + ['getAll' => 'true']);
		test_eq(200, $res['status'], 'HTTP status');
		test_assert(is_array($res['json']), 'Response is JSON array');
		test_eq($initialCount, count($res['json']), 'shuttle count');
	});

	run_case('GET /v2/shuttlebay/ with getClasses header returns shuttle classes and details', function () use ($baseUrl, $pdo, $getHeaders) {
		$expectedCount = (int) $pdo->query('SELECT COUNT(*) FROM esb_shuttle_classes')->fetchColumn();
		$res           = http_request($baseUrl, 'GET', $getHeaders + ['getClasses' => 'true']);
		test_eq(200, $res['status'], 'HTTP status');
		test_assert(is_array($res['json']), 'Response is JSON array');
		test_eq($expectedCount, count($res['json']), 'class count');

		$byName = [];
		foreach ($res['json'] as $row) {
			foreach (['id', 'name', 'type', 'capacity', 'armor', 'required_skill', 'required_skill_id'] as $col) {
				test_assert(array_key_exists($col, $row), "Class row missing key '{$col}'");
			}
			$byName[$row['name']] = $row;
		}

		$amma = $byName['Ammasarius-class multirole shuttle'] ?? null;
		test_assert($amma !== null, 'Expected Ammasarius-class multirole shuttle');
		test_eq(2, (int) $amma['id'], 'class id');
		test_eq('Personnel Carrier', $amma['type'], 'type');
		test_eq(16, (int) $amma['capacity'], 'capacity');
		test_eq('Resists small arms', $amma['armor'], 'armor');
		test_eq('Balance / Vliegbrevet', $amma['required_skill'], 'required_skill label');
		test_eq(31152, (int) $amma['required_skill_id'], 'required_skill_id');

		$razor = $byName['AM-50 Razor interceptor'] ?? null;
		test_assert($razor !== null, 'Expected AM-50 Razor interceptor');
		test_eq(null, $razor['armor'], 'nullable armor');
	});

	run_case('GET /v2/shuttlebay/ with id header and missing/conflicting/invalid headers', function () use ($baseUrl, $getHeaders) {
		$ok = http_request($baseUrl, 'GET', $getHeaders + ['id' => '5']);
		test_eq(200, $ok['status'], 'GET id=5 status');
		test_eq('Ambrotos', $ok['json'][0]['name'] ?? null, 'Ambrotos name');

		$pathGet = http_request($baseUrl . '5', 'GET', $getHeaders);
		test_eq(200, $pathGet['status'], 'GET /5 status');
		test_eq('Ambrotos', $pathGet['json'][0]['name'] ?? null, 'Ambrotos via path');

		$missing = http_request($baseUrl, 'GET', $getHeaders);
		test_eq(400, $missing['status'], 'missing header returns 400');

		$both = http_request($baseUrl, 'GET', $getHeaders + ['id' => '5', 'getAll' => 'true']);
		test_eq(400, $both['status'], 'both id and getAll returns 400');

		$allAndClasses = http_request($baseUrl, 'GET', $getHeaders + ['getAll' => 'true', 'getClasses' => 'true']);
		test_eq(400, $allAndClasses['status'], 'both getAll and getClasses returns 400');

		$idAndClasses = http_request($baseUrl, 'GET', $getHeaders + ['id' => '5', 'getClasses' => 'true']);
		test_eq(400, $idAndClasses['status'], 'both id and getClasses returns 400');

		$pathAndClasses = http_request($baseUrl . '5', 'GET', $getHeaders + ['getClasses' => 'true']);
		test_eq(400, $pathAndClasses['status'], 'path id and getClasses returns 400');

		$sqli = http_request($baseUrl, 'GET', $getHeaders + ['id' => '1 OR 1=1']);
		test_eq(400, $sqli['status'], 'SQLi id returns 400');

		$notFound = http_request($baseUrl, 'GET', $getHeaders + ['id' => '999999']);
		test_eq(404, $notFound['status'], 'missing shuttle returns 404');
	});

	// Create (POST)
	$authHeaders = [
		'Content-Type' => 'application/json',
		'token'        => $testToken,
	];

	$shuttleA = null;
	run_case('POST /v2/shuttlebay/ creates shuttle with minimal required fields (201)', function () use ($baseUrl, $authHeaders, &$createdIds, &$shuttleA) {
		$res = http_request($baseUrl, 'POST', $authHeaders, json_encode([
			'name'          => 'Hyperion Test',
			'serial_number' => 'HT-001',
			'class'         => 'Ammasarius-class multirole shuttle',
		]));
		test_eq(201, $res['status'], 'HTTP status');
		test_assert(is_array($res['json']), 'Valid JSON object response');
		test_assert((int) ($res['json']['id'] ?? 0) > 0, 'Assigned auto-increment id');
		$createdIds[] = (int) $res['json']['id'];
		$shuttleA     = $res['json'];

		test_eq('Hyperion Test', $shuttleA['name'], 'name');
		test_eq('HT-001', $shuttleA['serial_number'], 'serial_number');
		test_eq('Ammasarius-class multirole shuttle', $shuttleA['class'], 'class');
		test_eq(2, (int) $shuttleA['class_id'], 'class_id');
		test_eq('Personnel Carrier', $shuttleA['type'], 'type');
		test_eq(16, (int) $shuttleA['capacity'], 'capacity');
		test_eq('Bastion', $shuttleA['base'], 'default base');
		test_eq('Fully operational', $shuttleA['state'], 'default state');
		test_eq(1, (int) $shuttleA['operable'], 'default operable');
		test_eq('Ready', $shuttleA['status_name'], 'default status_name');
		test_eq(0, (int) $shuttleA['status'], 'default status');
		test_eq(31152, (int) $shuttleA['required_skill_id'], 'required_skill_id');
	});

	$shuttleB = null;
	run_case('POST /v2/shuttlebay/ creates shuttle with all optional lookup fields (201)', function () use ($baseUrl, $authHeaders, &$createdIds, &$shuttleB) {
		$res = http_request($baseUrl, 'POST', $authHeaders, json_encode([
			'name'             => 'Zephyr Test',
			'serial_number'    => 'ZT-002',
			'class'            => 'Vespa-class multirole shuttle',
			'type'             => 'Supply Carrier',
			'capacity'         => '8',
			'base'             => 'Fennik',
			'state'            => 'Requires refueling',
			'operable'         => '0',
			'status_name'      => 'Prep',
			'status'           => '1',
			'required_skill'   => 'Balance / Vliegbrevet',
			'assigned_to_id'   => '99',
			'assigned_to_name' => 'Sergei Konstantinova',
		]));
		test_eq(201, $res['status'], 'HTTP status');
		$shuttleB     = $res['json'];
		$createdIds[] = (int) $shuttleB['id'];
		test_eq('Fennik', $shuttleB['base'], 'base');
		test_eq('Requires refueling', $shuttleB['state'], 'state');
		test_eq(0, (int) $shuttleB['operable'], 'operable');
		test_eq('Prep', $shuttleB['status_name'], 'status_name');
		test_eq(1, (int) $shuttleB['status'], 'status');
		test_eq(99, (int) $shuttleB['assigned_to_id'], 'assigned_to_id');
		test_eq('Sergei Konstantinova', $shuttleB['assigned_to_name'], 'assigned_to_name');
	});

	run_case('GET /v2/shuttlebay/ with getAll header includes newly created shuttles', function () use ($baseUrl, $initialCount, $getHeaders, &$shuttleA, &$shuttleB) {
		$res = http_request($baseUrl, 'GET', $getHeaders + ['getAll' => 'true']);
		test_eq(200, $res['status'], 'HTTP status');
		test_eq($initialCount + 2, count($res['json']), 'updated count');
		$ids = array_map('intval', array_column($res['json'], 'id'));
		test_assert(in_array((int) $shuttleA['id'], $ids, true), 'contains shuttleA');
		test_assert(in_array((int) $shuttleB['id'], $ids, true), 'contains shuttleB');
	});

	run_case('POST /v2/shuttlebay/ validation error cases return 400 with valid JSON', function () use ($baseUrl, $authHeaders) {
		$cases = [
			'empty body'               => '',
			'malformed JSON'           => '{"name": "bad"',
			'JSON list instead object' => '["a", "b"]',
			'empty object'             => '{}',
			'missing name'             => json_encode(['serial_number' => 'X-1', 'class' => 'Runner-Class']),
			'missing serial_number'    => json_encode(['name' => 'X', 'class' => 'Runner-Class']),
			'missing class'            => json_encode(['name' => 'X', 'serial_number' => 'X-1']),
			'whitespace name'          => json_encode(['name' => '   ', 'serial_number' => 'X-1', 'class' => 'Runner-Class']),
			'serial_number > 10 chars' => json_encode(['name' => 'X', 'serial_number' => 'TOOLONG-123', 'class' => 'Runner-Class']),
			'unknown class'            => json_encode(['name' => 'X', 'serial_number' => 'X-1', 'class' => 'NoSuchClass']),
			'unknown base'             => json_encode(['name' => 'X', 'serial_number' => 'X-1', 'class' => 'Runner-Class', 'base' => 'MoonBase']),
			'unknown state'            => json_encode(['name' => 'X', 'serial_number' => 'X-1', 'class' => 'Runner-Class', 'state' => 'Exploded']),
			'invalid operable'         => json_encode(['name' => 'X', 'serial_number' => 'X-1', 'class' => 'Runner-Class', 'operable' => '9']),
			'state/operable conflict'  => json_encode(['name' => 'X', 'serial_number' => 'X-1', 'class' => 'Runner-Class', 'state' => 'Fully operational', 'operable' => '0']),
			'unknown status id'        => json_encode(['name' => 'X', 'serial_number' => 'X-1', 'class' => 'Runner-Class', 'status' => '42']),
			'unknown status_name'      => json_encode(['name' => 'X', 'serial_number' => 'X-1', 'class' => 'Runner-Class', 'status_name' => 'Destroyed']),
			'deleted status id -999'   => json_encode(['name' => 'X', 'serial_number' => 'X-1', 'class' => 'Runner-Class', 'status' => '-999']),
			'deleted status_name'      => json_encode(['name' => 'X', 'serial_number' => 'X-1', 'class' => 'Runner-Class', 'status_name' => 'Deleted']),
			'status mismatch'          => json_encode(['name' => 'X', 'serial_number' => 'X-1', 'class' => 'Runner-Class', 'status' => '0', 'status_name' => 'On Mission']),
			'bad capacity'             => json_encode(['name' => 'X', 'serial_number' => 'X-1', 'class' => 'Runner-Class', 'capacity' => 'lots']),
			'unknown assigned_to_id'   => json_encode(['name' => 'X', 'serial_number' => 'X-1', 'class' => 'Runner-Class', 'assigned_to_id' => 999999]),
		];
		foreach ($cases as $label => $payload) {
			$res = http_request($baseUrl, 'POST', $authHeaders, $payload);
			test_eq(400, $res['status'], "POST {$label}");
			test_assert(is_array($res['json']) && isset($res['json']['error']), "POST {$label} JSON error key");
		}

		$withPathId = http_request($baseUrl . '5', 'POST', $authHeaders, json_encode([
			'name'          => 'PathIdErr',
			'serial_number' => 'PI-01',
			'class'         => 'Runner-Class',
		]));
		test_eq(400, $withPathId['status'], 'POST to /:id rejected with 400');
	});

	// Update (PUT)
	run_case('PUT /v2/shuttlebay/:id updates fields with stale GET metadata preserved', function () use ($baseUrl, $authHeaders, &$shuttleA) {
		$id = (int) $shuttleA['id'];

		// Simulate Angular sending previous GET record with ONLY name, base, class, status_name, state edited
		$payload                  = $shuttleA;
		$payload['name']          = 'Hyperion Prime';
		$payload['serial_number'] = 'HP-009';
		$payload['class']         = 'Runner-Class'; // stale class_id=2 kept from GET!
		$payload['base']          = 'FOB Wyvern';
		$payload['status_name']   = 'On Mission'; // stale status=0 kept from GET!
		$payload['state']         = 'Undergoing modification'; // stale operable=1 kept from GET!
		$payload['assigned_to_id'] = '99';

		$res = http_request($baseUrl . $id, 'PUT', $authHeaders, json_encode($payload));
		test_eq(200, $res['status'], 'PUT HTTP status');
		$updated = $res['json'];
		test_eq($id, (int) $updated['id'], 'id unchanged');
		test_eq('Hyperion Prime', $updated['name'], 'updated name');
		test_eq('HP-009', $updated['serial_number'], 'updated serial_number');
		test_eq('Runner-Class', $updated['class'], 'updated class');
		test_eq(3, (int) $updated['class_id'], 'updated class_id');
		test_eq('Fast Supply Carrier', $updated['type'], 'derived type updated');
		test_eq(2, (int) $updated['capacity'], 'derived capacity updated');
		test_eq('FOB Wyvern', $updated['base'], 'updated base');
		test_eq(2, (int) $updated['status'], 'updated status from status_name');
		test_eq('On Mission', $updated['status_name'], 'updated status_name');
		test_eq('Undergoing modification', $updated['state'], 'updated state');
		test_eq(0, (int) $updated['operable'], 'updated operable');
		test_eq(99, (int) $updated['assigned_to_id'], 'updated assigned_to_id');
		$shuttleA = $updated;
	});

	run_case('PUT /v2/shuttlebay/:id handles status/class_id edits, unassign, and no-op round-trip', function () use ($baseUrl, $authHeaders, &$shuttleA) {
		$id = (int) $shuttleA['id'];

		// Unchanged no-op PUT returns 200 (NOT 404)
		$noop = http_request($baseUrl . $id . '/', 'PUT', $authHeaders, json_encode($shuttleA));
		test_eq(200, $noop['status'], 'no-op PUT returns 200');

		// Change via status id (-1), class_id (5 = Elmaris), operable (1), clear assigned_to_id to null
		$payload                   = $shuttleA;
		$payload['status']         = '-1'; // stale status_name "On Mission" kept
		$payload['class_id']       = '5';  // stale class "Runner-Class" kept
		$payload['operable']       = '1';  // stale state "Undergoing modification" kept
		$payload['assigned_to_id'] = null; // stale assigned_to_name kept

		$res = http_request($baseUrl . $id, 'PUT', $authHeaders, json_encode($payload));
		test_eq(200, $res['status'], 'PUT edit via IDs');
		$updated = $res['json'];
		test_eq(-1, (int) $updated['status'], 'status -1');
		test_eq('In-Repair', $updated['status_name'], 'status_name In-Repair');
		test_eq('Elmaris', $updated['class'], 'class Elmaris');
		test_eq(5, (int) $updated['class_id'], 'class_id 5');
		test_eq(1, (int) $updated['operable'], 'operable 1');
		test_eq('Fully operational', $updated['state'], 'state Fully operational');
		test_eq(null, $updated['assigned_to_id'], 'assigned_to_id cleared');
		test_eq(null, $updated['assigned_to_name'], 'assigned_to_name cleared');
		$shuttleA = $updated;
	});

	run_case('PUT /v2/shuttlebay/:id returns 404 for unknown id and 400 for validation errors', function () use ($baseUrl, $authHeaders, &$shuttleA) {
		$validBody = json_encode([
			'name'          => 'Ghost',
			'serial_number' => 'GH-01',
			'class'         => 'Runner-Class',
		]);

		$missing = http_request($baseUrl . '999999', 'PUT', $authHeaders, $validBody);
		test_eq(404, $missing['status'], 'unknown id 404');
		test_assert(isset($missing['json']['error']), '404 has JSON error');

		$noId = http_request($baseUrl, 'PUT', $authHeaders, $validBody);
		test_eq(400, $noId['status'], 'missing id in path 400');

		foreach (['abc', '0', '-5', '999999999999999999'] as $badId) {
			$res = http_request($baseUrl . $badId, 'PUT', $authHeaders, $validBody);
			test_eq(400, $res['status'], "invalid id '{$badId}' returns 400");
		}

		$id       = (int) $shuttleA['id'];
		$mismatch = $shuttleA;
		$mismatch['id'] = $id + 100;
		test_eq(400, http_request($baseUrl . $id, 'PUT', $authHeaders, json_encode($mismatch))['status'], 'body id mismatch 400');

		$delStatus = $shuttleA;
		$delStatus['status'] = '-999';
		test_eq(400, http_request($baseUrl . $id, 'PUT', $authHeaders, json_encode($delStatus))['status'], 'PUT status -999 returns 400');

		$delName = $shuttleA;
		$delName['status_name'] = 'Deleted';
		test_eq(400, http_request($baseUrl . $id, 'PUT', $authHeaders, json_encode($delName))['status'], 'PUT status_name Deleted returns 400');

		$longSerial = $shuttleA;
		$longSerial['serial_number'] = 'EXCEEDS-10X';
		test_eq(400, http_request($baseUrl . $id, 'PUT', $authHeaders, json_encode($longSerial))['status'], 'PUT long serial 400');

		$missingName = ['serial_number' => 'OK-1', 'class' => 'Runner-Class'];
		test_eq(400, http_request($baseUrl . $id, 'PUT', $authHeaders, json_encode($missingName))['status'], 'PUT missing name 400');

		$badJson = http_request($baseUrl . $id, 'PUT', $authHeaders, '{bad json');
		test_eq(400, $badJson['status'], 'PUT malformed JSON 400');

		$statusConflict = $shuttleA;
		$statusConflict['status']      = '2';
		$statusConflict['status_name'] = 'Missing';
		test_eq(400, http_request($baseUrl . $id, 'PUT', $authHeaders, json_encode($statusConflict))['status'], 'PUT conflicting status/status_name 400');

		$stateConflict = $shuttleA;
		$stateConflict['state']    = 'Light Damage (Can still fly)'; // operable=1
		$stateConflict['operable'] = '0';
		test_eq(400, http_request($baseUrl . $id, 'PUT', $authHeaders, json_encode($stateConflict))['status'], 'PUT conflicting state/operable 400');

		$classConflict = $shuttleA;
		$classConflict['class']    = 'Runner-Class'; // id 3
		$classConflict['class_id'] = '1';            // AM-50 Razor
		test_eq(400, http_request($baseUrl . $id, 'PUT', $authHeaders, json_encode($classConflict))['status'], 'PUT conflicting class/class_id 400');

		$nested = http_request($baseUrl . $id . '/extra', 'PUT', $authHeaders, $validBody);
		test_eq(404, $nested['status'], 'PUT nested path 404');
	});

	// Delete (DELETE - soft delete via status = -999)
	run_case('DELETE /v2/shuttlebay/:id soft-deletes using status -999 and hides from subsequent GET/PUT/DELETE', function () use ($baseUrl, $authHeaders, $pdo, $initialCount, &$shuttleA) {
		$id = (int) $shuttleA['id'];

		$del = http_request($baseUrl . $id, 'DELETE', $authHeaders);
		test_eq(200, $del['status'], 'DELETE returns 200');
		test_eq(true, $del['json']['deleted'] ?? null, 'deleted=true');
		test_eq($id, (int) ($del['json']['id'] ?? 0), 'deleted id');
		test_eq(-999, (int) ($del['json']['status'] ?? 0), 'deleted status -999');
		test_eq('Deleted', $del['json']['status_name'] ?? null, 'status_name Deleted');

		// Confirm row still exists in esb_shuttles with status = -999
		$dbStatus = $pdo->query("SELECT status FROM esb_shuttles WHERE id = {$id}")->fetchColumn();
		test_eq(-999, (int) $dbStatus, 'DB row has status -999');

		// GET all excludes the soft-deleted shuttle (shuttleB still exists)
		$list = http_request($baseUrl, 'GET', $authHeaders + ['getAll' => 'true']);
		test_eq(200, $list['status'], 'GET all returns 200');
		test_eq($initialCount + 1, count($list['json']), 'deleted shuttle excluded from getAll');
		$ids = array_map('intval', array_column($list['json'], 'id'));
		test_assert(! in_array($id, $ids, true), 'deleted shuttle id absent from getAll');

		// GET by id returns 404
		test_eq(404, http_request($baseUrl . $id, 'GET', $authHeaders)['status'], 'GET /:id returns 404 after delete');
		test_eq(404, http_request($baseUrl, 'GET', $authHeaders + ['id' => (string) $id])['status'], 'GET header id returns 404 after delete');

		// PUT returns 404 on soft-deleted shuttle
		$putDeleted = http_request($baseUrl . $id, 'PUT', $authHeaders, json_encode([
			'name'          => 'Revive',
			'serial_number' => 'RV-01',
			'class'         => 'Runner-Class',
		]));
		test_eq(404, $putDeleted['status'], 'PUT on deleted shuttle returns 404');

		// Repeat DELETE returns 404
		test_eq(404, http_request($baseUrl . $id, 'DELETE', $authHeaders)['status'], 'repeat DELETE returns 404');
	});

	run_case('DELETE /v2/shuttlebay/ error handling (404 / 400)', function () use ($baseUrl, $authHeaders) {
		test_eq(404, http_request($baseUrl . '999999', 'DELETE', $authHeaders)['status'], 'unknown id 404');
		test_eq(400, http_request($baseUrl, 'DELETE', $authHeaders)['status'], 'missing id 400');
		test_eq(400, http_request($baseUrl . 'abc', 'DELETE', $authHeaders)['status'], 'invalid id 400');
	});

	run_case('PATCH /v2/shuttlebay/ returns 501 with JSON body', function () use ($baseUrl, $authHeaders) {
		$res = http_request($baseUrl, 'PATCH', $authHeaders);
		test_eq(501, $res['status'], 'PATCH 501');
		test_assert(is_array($res['json']) && isset($res['json']['error']), 'PATCH returns JSON error');
	});
} finally {
	if (is_resource($serverProc)) {
		proc_terminate($serverProc);
		proc_close($serverProc);
	}
	if (file_exists($routerFile)) {
		@unlink($routerFile);
	}
	if (! empty($createdIds)) {
		$placeholders = implode(',', array_fill(0, count($createdIds), '?'));
		$delStmt      = $pdo->prepare("DELETE FROM esb_shuttles WHERE id IN ({$placeholders})");
		$delStmt->execute($createdIds);
	}
	$pdo->exec('ALTER TABLE esb_shuttles AUTO_INCREMENT = ' . ($initialMaxShut + 1));
	if ($tokenId !== null) {
		$tokDel = $pdo->prepare('DELETE FROM eos_tokens WHERE token_id = ?');
		$tokDel->execute([$tokenId]);
		$pdo->exec('ALTER TABLE eos_tokens AUTO_INCREMENT = ' . ($initialMaxTok + 1));
	}
}

echo "\nResults: {$passed} passed, {$failed} failed.\n";
exit($failed > 0 ? 1 : 0);
