<?php

class Shuttlebay
{
	public const STATUS_DELETED           = -999;
	public const DEFAULT_STATUS_ID        = 0;
	public const DEFAULT_CONDITION_ID     = 1;
	public const DEFAULT_BASE_ID          = 1;
	public const SERIAL_NUMBER_MAX_LENGTH = 10;

	/** @var PDO */
	private $conn;

	/**
	 * @param PDO|null $conn Optional PDO connection (defaults to Database::$conn).
	 */
	public function __construct(?PDO $conn = null)
	{
		$this->conn = $conn !== null ? $conn : Database::$conn;
	}

	/**
	 * Executes a SQL query with optional bound parameters and returns all rows.
	 *
	 * @param string $sql    SQL statement to prepare and execute.
	 * @param array  $params Bound parameter values.
	 * @return array
	 */
	public function runQuery($sql, array $params = [])
	{
		$stmt = $this->conn->prepare($sql);
		if ($stmt->execute($params) === false) {
			$info = $stmt->errorInfo();
			throw new RuntimeException('Database query failed: ' . ($info[2] ?? 'unknown error'));
		}

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Returns all non-deleted shuttles ordered by name.
	 *
	 * @return array
	 */
	public function getAllShuttles()
	{
		$sql = $this->baseSelectSql() . '
		WHERE s.status != ?
		ORDER BY s.name';

		return $this->runQuery($sql, [self::STATUS_DELETED]);
	}

	/**
	 * Returns matching non-deleted shuttle(s) as a list for backwards compatibility with GET.
	 *
	 * @param mixed $id Shuttle ID.
	 * @return array
	 */
	public function getShuttle($id)
	{
		$parsedId = $this->parsePositiveInt($id);
		if ($parsedId === null) {
			return [];
		}

		$sql = $this->baseSelectSql() . '
		WHERE s.id = ? AND s.status != ?
		ORDER BY s.name';

		return $this->runQuery($sql, [$parsedId, self::STATUS_DELETED]);
	}

	/**
	 * Returns a single non-deleted shuttle record, or null if not found / soft-deleted.
	 *
	 * @param int $id Shuttle ID.
	 * @return array|null
	 */
	public function getShuttleRecord(int $id): ?array
	{
		$rows = $this->getShuttle($id);
		return isset($rows[0]) ? $rows[0] : null;
	}

	/**
	 * Creates a new shuttle in esb_shuttles and returns the joined shuttle record.
	 *
	 * @param array $data Decoded JSON payload.
	 * @return array
	 * @throws InvalidArgumentException When required fields are missing or validation/lookup fails.
	 * @throws RuntimeException When the insert or post-insert fetch fails unexpectedly.
	 */
	public function createShuttle(array $data): array
	{
		$this->assertRequiredFieldsPresent($data, ['name', 'serial_number', 'class']);

		$name         = $this->validateRequiredString($data['name'], 'name');
		$serialNumber = $this->validateSerialNumber($data['serial_number']);

		$classId = $this->resolveClassForCreate($data);
		$this->validateDerivedClassFields($data);

		$baseId      = $this->resolveBaseForCreate($data);
		$conditionId = $this->resolveConditionForCreate($data);
		$statusId    = $this->resolveStatusForCreate($data);
		$assignedTo  = $this->resolveAssigneeForCreate($data);

		$stmt = $this->conn->prepare(
			'INSERT INTO esb_shuttles (name, serial_number, class, status, base_location, `condition`, assigned_to)
			 VALUES (?, ?, ?, ?, ?, ?, ?)'
		);
		$ok = $stmt->execute([
			$name,
			$serialNumber,
			$classId,
			$statusId,
			$baseId,
			$conditionId,
			$assignedTo,
		]);
		if ($ok === false) {
			$info = $stmt->errorInfo();
			throw new RuntimeException('Failed to create shuttle: ' . ($info[2] ?? 'unknown error'));
		}

		$newId   = (int) $this->conn->lastInsertId();
		$created = $this->getShuttleRecord($newId);
		if ($created === null) {
			throw new RuntimeException('Shuttle was created (id ' . $newId . ') but could not be reloaded with lookup joins.');
		}

		return $created;
	}

	/**
	 * Updates an existing non-deleted shuttle and returns the updated joined record, or null if not found.
	 *
	 * @param int   $id   Route shuttle ID.
	 * @param array $data Decoded JSON payload.
	 * @return array|null
	 * @throws InvalidArgumentException When validation or lookup resolution fails.
	 * @throws RuntimeException When the update fails unexpectedly.
	 */
	public function updateShuttle(int $id, array $data): ?array
	{
		$existing    = $this->getShuttleRecord($id);
		$existingRaw = $this->getRawShuttle($id);
		if ($existing === null || $existingRaw === null) {
			return null;
		}

		if ($this->hasValue($data, 'id')) {
			$bodyId = $this->parsePositiveInt($data['id']);
			if ($bodyId === null || $bodyId !== $id) {
				throw new InvalidArgumentException("Body 'id' must match the route id (" . $id . ').');
			}
		}

		$missing = [];
		if (! $this->hasValue($data, 'name')) {
			$missing[] = 'name';
		}
		if (! $this->hasValue($data, 'serial_number')) {
			$missing[] = 'serial_number';
		}
		if (! $this->hasValue($data, 'class') && ! $this->hasValue($data, 'class_id')) {
			$missing[] = 'class';
		}
		if (! empty($missing)) {
			throw new InvalidArgumentException('Missing required field(s): ' . implode(', ', $missing) . '.');
		}

		$name         = $this->validateRequiredString($data['name'], 'name');
		$serialNumber = $this->validateSerialNumber($data['serial_number']);

		$classId = $this->resolveClassForUpdate($data, $existing);
		$this->validateDerivedClassFields($data);

		$baseId      = $this->resolveBaseForUpdate($data, (int) $existingRaw['base_location']);
		$conditionId = $this->resolveConditionForUpdate($data, $existing, (int) $existingRaw['condition']);
		$statusId    = $this->resolveStatusForUpdate($data, $existing);
		$assignedTo  = $this->resolveAssigneeForUpdate($data, $existing);

		$stmt = $this->conn->prepare(
			'UPDATE esb_shuttles
			 SET name = ?, serial_number = ?, class = ?, status = ?, base_location = ?, `condition` = ?, assigned_to = ?
			 WHERE id = ? AND status != ?'
		);
		$ok = $stmt->execute([
			$name,
			$serialNumber,
			$classId,
			$statusId,
			$baseId,
			$conditionId,
			$assignedTo,
			$id,
			self::STATUS_DELETED,
		]);
		if ($ok === false) {
			$info = $stmt->errorInfo();
			throw new RuntimeException('Failed to update shuttle: ' . ($info[2] ?? 'unknown error'));
		}

		$updated = $this->getShuttleRecord($id);
		if ($updated === null) {
			throw new RuntimeException('Shuttle (id ' . $id . ') could not be reloaded after update.');
		}

		return $updated;
	}

	/**
	 * Soft-deletes a shuttle by setting its status to STATUS_DELETED (-999 = "Deleted").
	 *
	 * @param int $id Shuttle ID.
	 * @return bool True if soft-deleted, false if the shuttle does not exist or is already deleted.
	 */
	public function deleteShuttle(int $id): bool
	{
		$stmt = $this->conn->prepare(
			'UPDATE esb_shuttles SET status = ? WHERE id = ? AND status != ?'
		);
		$ok = $stmt->execute([self::STATUS_DELETED, $id, self::STATUS_DELETED]);
		if ($ok === false) {
			$info = $stmt->errorInfo();
			throw new RuntimeException('Failed to delete shuttle: ' . ($info[2] ?? 'unknown error'));
		}

		return $stmt->rowCount() > 0;
	}

	private function baseSelectSql(): string
	{
		return <<<SQL
		SELECT s.id, s.name, s.serial_number, c.name AS class, c.id AS class_id, c.`type` AS type, c.capacity, b.name AS base,
		cond.name AS state, cond.operable AS operable, stat.`status` AS status_name, s.status AS status,
		chars.character_name AS assigned_to_name, chars.characterID AS assigned_to_id,
		skill.label AS required_skill, c.required_skill AS required_skill_id
		FROM esb_shuttles s
		JOIN esb_shuttle_classes c ON s.class = c.id
		JOIN esb_shuttle_bases b ON b.id = s.base_location
		JOIN esb_shuttle_conditions cond ON cond.id = s.condition
		JOIN esb_shuttle_status stat ON stat.id = s.status
		LEFT JOIN ecc_characters chars ON chars.characterID = s.assigned_to
		JOIN ecc_skills_allskills skill ON ((skill.skill_id = c.required_skill) AND (c.id = s.class))
		SQL;
	}

	private function getRawShuttle(int $id): ?array
	{
		$stmt = $this->conn->prepare(
			'SELECT id, name, serial_number, class, status, base_location, `condition`, assigned_to
			 FROM esb_shuttles
			 WHERE id = ? AND status != ?'
		);
		$stmt->execute([$id, self::STATUS_DELETED]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row === false ? null : $row;
	}

	private function hasValue(array $data, string $key): bool
	{
		if (! array_key_exists($key, $data)) {
			return false;
		}
		$value = $data[$key];
		if ($value === null) {
			return false;
		}
		if (is_string($value) && trim($value) === '') {
			return false;
		}

		return true;
	}

	private function assertRequiredFieldsPresent(array $data, array $fields): void
	{
		$missing = [];
		foreach ($fields as $field) {
			if (! $this->hasValue($data, $field)) {
				$missing[] = $field;
			}
		}
		if (! empty($missing)) {
			throw new InvalidArgumentException('Missing required field(s): ' . implode(', ', $missing) . '.');
		}
	}

	private function validateRequiredString($value, string $field): string
	{
		if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
			throw new InvalidArgumentException("Field '" . $field . "' must be a non-empty string.");
		}
		$trimmed = trim((string) $value);
		if ($trimmed === '') {
			throw new InvalidArgumentException("Field '" . $field . "' cannot be empty.");
		}

		return $trimmed;
	}

	private function validateSerialNumber($value): string
	{
		$serial = $this->validateRequiredString($value, 'serial_number');
		$length = function_exists('mb_strlen') ? mb_strlen($serial, 'UTF-8') : strlen($serial);
		if ($length > self::SERIAL_NUMBER_MAX_LENGTH) {
			throw new InvalidArgumentException(
				"Field 'serial_number' cannot exceed " . self::SERIAL_NUMBER_MAX_LENGTH . ' characters.'
			);
		}

		return $serial;
	}

	private function validateDerivedClassFields(array $data): void
	{
		if ($this->hasValue($data, 'type') && ! is_string($data['type'])) {
			throw new InvalidArgumentException("Field 'type' must be a string.");
		}

		if ($this->hasValue($data, 'capacity')) {
			$capacity = $this->parseSignedInt($data['capacity']);
			if ($capacity === null || $capacity < 0) {
				throw new InvalidArgumentException("Field 'capacity' must be a non-negative integer.");
			}
		}

		if ($this->hasValue($data, 'required_skill') && ! is_string($data['required_skill'])) {
			throw new InvalidArgumentException("Field 'required_skill' must be a string.");
		}

		if ($this->hasValue($data, 'required_skill_id') && $this->parsePositiveInt($data['required_skill_id']) === null) {
			throw new InvalidArgumentException("Field 'required_skill_id' must be a positive integer.");
		}
	}

	private function resolveClassForCreate(array $data): int
	{
		$className = $this->validateRequiredString($data['class'], 'class');
		$classRow  = $this->findClassByName($className);
		if ($classRow === null) {
			throw new InvalidArgumentException(
				"Unknown shuttle class '" . $className . "'. Valid classes: " . implode(', ', $this->listClassNames()) . '.'
			);
		}

		if ($this->hasValue($data, 'class_id')) {
			$classId = $this->parsePositiveInt($data['class_id']);
			if ($classId === null) {
				throw new InvalidArgumentException("Field 'class_id' must be a positive integer.");
			}
			$byId = $this->findClassById($classId);
			if ($byId === null) {
				throw new InvalidArgumentException("Unknown shuttle class_id '" . $classId . "'.");
			}
			if ((int) $byId['id'] !== (int) $classRow['id']) {
				throw new InvalidArgumentException(
					"Conflicting 'class' ('" . $className . "') and 'class_id' (" . $classId . ').'
				);
			}
		}

		return (int) $classRow['id'];
	}

	private function resolveClassForUpdate(array $data, array $existing): int
	{
		$hasClass   = $this->hasValue($data, 'class');
		$hasClassId = $this->hasValue($data, 'class_id');

		$classChanged   = false;
		$classIdChanged = false;
		$className      = null;
		$classId        = null;

		if ($hasClass) {
			$className    = $this->validateRequiredString($data['class'], 'class');
			$classChanged = ! $this->sameText($className, (string) $existing['class']);
		}

		if ($hasClassId) {
			$classId = $this->parsePositiveInt($data['class_id']);
			if ($classId === null) {
				throw new InvalidArgumentException("Field 'class_id' must be a positive integer.");
			}
			$classIdChanged = ($classId !== (int) $existing['class_id']);
		}

		if ($classChanged && $classIdChanged) {
			$byName = $this->findClassByName($className);
			if ($byName === null) {
				throw new InvalidArgumentException(
					"Unknown shuttle class '" . $className . "'. Valid classes: " . implode(', ', $this->listClassNames()) . '.'
				);
			}
			$byId = $this->findClassById($classId);
			if ($byId === null) {
				throw new InvalidArgumentException("Unknown shuttle class_id '" . $classId . "'.");
			}
			if ((int) $byName['id'] !== (int) $byId['id']) {
				throw new InvalidArgumentException(
					"Conflicting 'class' ('" . $className . "') and 'class_id' (" . $classId . ').'
				);
			}
			return (int) $byName['id'];
		}

		if ($classChanged) {
			$byName = $this->findClassByName($className);
			if ($byName === null) {
				throw new InvalidArgumentException(
					"Unknown shuttle class '" . $className . "'. Valid classes: " . implode(', ', $this->listClassNames()) . '.'
				);
			}
			return (int) $byName['id'];
		}

		if ($classIdChanged) {
			$byId = $this->findClassById($classId);
			if ($byId === null) {
				throw new InvalidArgumentException("Unknown shuttle class_id '" . $classId . "'.");
			}
			return (int) $byId['id'];
		}

		return (int) $existing['class_id'];
	}

	private function resolveBaseForCreate(array $data): int
	{
		if (! $this->hasValue($data, 'base')) {
			$default = $this->findDefaultBaseId();
			if ($default === null) {
				throw new RuntimeException('No shuttle bases configured in esb_shuttle_bases.');
			}
			return $default;
		}

		return $this->resolveBaseValue($data['base']);
	}

	private function resolveBaseForUpdate(array $data, int $existingBaseId): int
	{
		if (! $this->hasValue($data, 'base')) {
			return $existingBaseId;
		}

		return $this->resolveBaseValue($data['base']);
	}

	private function resolveBaseValue($rawBase): int
	{
		if (! is_string($rawBase) && ! is_int($rawBase)) {
			throw new InvalidArgumentException("Field 'base' must be a string.");
		}
		$value = trim((string) $rawBase);
		$byName = $this->findBaseByName($value);
		if ($byName !== null) {
			return (int) $byName['id'];
		}

		$parsedId = $this->parsePositiveInt($rawBase);
		if ($parsedId !== null) {
			$byId = $this->findBaseById($parsedId);
			if ($byId !== null) {
				return (int) $byId['id'];
			}
		}

		throw new InvalidArgumentException(
			"Unknown base '" . $value . "'. Valid bases: " . implode(', ', $this->listBaseNames()) . '.'
		);
	}

	private function resolveConditionForCreate(array $data): int
	{
		$hasState    = $this->hasValue($data, 'state');
		$hasOperable = $this->hasValue($data, 'operable');

		$operable = null;
		if ($hasOperable) {
			$operable = $this->parseOperable($data['operable']);
		}

		if ($hasState) {
			$stateName = $this->validateRequiredString($data['state'], 'state');
			$condRow   = $this->findConditionByName($stateName);
			if ($condRow === null) {
				throw new InvalidArgumentException(
					"Unknown state '" . $stateName . "'. Valid states: " . implode(', ', $this->listConditionNames()) . '.'
				);
			}
			if ($hasOperable && (int) $condRow['operable'] !== $operable) {
				throw new InvalidArgumentException(
					"Conflicting 'state' ('" . $stateName . "' has operable=" . (int) $condRow['operable'] . ") and 'operable' (" . $operable . ').'
				);
			}
			return (int) $condRow['id'];
		}

		if ($hasOperable) {
			$condRow = $this->findDefaultConditionForOperable($operable);
			if ($condRow === null) {
				throw new InvalidArgumentException("No shuttle condition matches operable='" . $operable . "'.");
			}
			return (int) $condRow['id'];
		}

		$default = $this->findDefaultConditionId();
		if ($default === null) {
			throw new RuntimeException('No shuttle conditions configured in esb_shuttle_conditions.');
		}
		return $default;
	}

	private function resolveConditionForUpdate(array $data, array $existing, int $existingConditionId): int
	{
		$hasState    = $this->hasValue($data, 'state');
		$hasOperable = $this->hasValue($data, 'operable');

		$stateName    = null;
		$stateChanged = false;
		if ($hasState) {
			$stateName    = $this->validateRequiredString($data['state'], 'state');
			$stateChanged = ! $this->sameText($stateName, (string) $existing['state']);
		}

		$operable        = null;
		$operableChanged = false;
		if ($hasOperable) {
			$operable        = $this->parseOperable($data['operable']);
			$operableChanged = ($operable !== (int) $existing['operable']);
		}

		if ($stateChanged && $operableChanged) {
			$condRow = $this->findConditionByName($stateName);
			if ($condRow === null) {
				throw new InvalidArgumentException(
					"Unknown state '" . $stateName . "'. Valid states: " . implode(', ', $this->listConditionNames()) . '.'
				);
			}
			if ((int) $condRow['operable'] !== $operable) {
				throw new InvalidArgumentException(
					"Conflicting 'state' ('" . $stateName . "' has operable=" . (int) $condRow['operable'] . ") and 'operable' (" . $operable . ').'
				);
			}
			return (int) $condRow['id'];
		}

		if ($stateChanged) {
			$condRow = $this->findConditionByName($stateName);
			if ($condRow === null) {
				throw new InvalidArgumentException(
					"Unknown state '" . $stateName . "'. Valid states: " . implode(', ', $this->listConditionNames()) . '.'
				);
			}
			return (int) $condRow['id'];
		}

		if ($operableChanged) {
			$condRow = $this->findDefaultConditionForOperable($operable);
			if ($condRow === null) {
				throw new InvalidArgumentException("No shuttle condition matches operable='" . $operable . "'.");
			}
			return (int) $condRow['id'];
		}

		return $existingConditionId;
	}

	private function resolveStatusForCreate(array $data): int
	{
		$hasStatus     = $this->hasValue($data, 'status');
		$hasStatusName = $this->hasValue($data, 'status_name');

		$byId   = null;
		$byName = null;

		if ($hasStatus) {
			$byId = $this->resolveStatusById($data['status']);
		}
		if ($hasStatusName) {
			$byName = $this->resolveStatusByName($data['status_name']);
		}

		if ($byId !== null && $byName !== null) {
			if ((int) $byId['id'] !== (int) $byName['id']) {
				throw new InvalidArgumentException(
					"Conflicting 'status' (" . (int) $byId['id'] . ": '" . $byId['status'] . "') and 'status_name' ('" . $byName['status'] . "')."
				);
			}
			return (int) $byId['id'];
		}

		if ($byId !== null) {
			return (int) $byId['id'];
		}

		if ($byName !== null) {
			return (int) $byName['id'];
		}

		return self::DEFAULT_STATUS_ID;
	}

	private function resolveStatusForUpdate(array $data, array $existing): int
	{
		$hasStatus     = $this->hasValue($data, 'status');
		$hasStatusName = $this->hasValue($data, 'status_name');

		$statusChanged     = false;
		$statusNameChanged = false;
		$parsedStatus      = null;
		$statusName        = null;

		if ($hasStatus) {
			$parsedStatus = $this->parseSignedInt($data['status']);
			if ($parsedStatus === null) {
				throw new InvalidArgumentException("Field 'status' must be an integer.");
			}
			if ($parsedStatus === self::STATUS_DELETED) {
				throw new InvalidArgumentException(
					'Status ' . self::STATUS_DELETED . ' (Deleted) cannot be assigned directly. Use DELETE /v2/shuttlebay/:id instead.'
				);
			}
			$statusChanged = ($parsedStatus !== (int) $existing['status']);
		}

		if ($hasStatusName) {
			$statusName = $this->validateRequiredString($data['status_name'], 'status_name');
			if ($this->sameText($statusName, 'Deleted')) {
				throw new InvalidArgumentException(
					"Status 'Deleted' (" . self::STATUS_DELETED . ') cannot be assigned directly. Use DELETE /v2/shuttlebay/:id instead.'
				);
			}
			$statusNameChanged = ! $this->sameText($statusName, (string) $existing['status_name']);
		}

		if ($statusChanged && $statusNameChanged) {
			$byId   = $this->resolveStatusById($parsedStatus);
			$byName = $this->resolveStatusByName($statusName);
			if ((int) $byId['id'] !== (int) $byName['id']) {
				throw new InvalidArgumentException(
					"Conflicting 'status' (" . (int) $byId['id'] . ": '" . $byId['status'] . "') and 'status_name' ('" . $byName['status'] . "')."
				);
			}
			return (int) $byId['id'];
		}

		if ($statusChanged) {
			$byId = $this->resolveStatusById($parsedStatus);
			return (int) $byId['id'];
		}

		if ($statusNameChanged) {
			$byName = $this->resolveStatusByName($statusName);
			return (int) $byName['id'];
		}

		return (int) $existing['status'];
	}

	private function resolveStatusById($rawStatus): array
	{
		$statusId = $this->parseSignedInt($rawStatus);
		if ($statusId === null) {
			throw new InvalidArgumentException("Field 'status' must be an integer.");
		}
		if ($statusId === self::STATUS_DELETED) {
			throw new InvalidArgumentException(
				'Status ' . self::STATUS_DELETED . ' (Deleted) cannot be assigned directly. Use DELETE /v2/shuttlebay/:id instead.'
			);
		}
		$row = $this->findStatusById($statusId);
		if ($row === null) {
			throw new InvalidArgumentException(
				"Unknown status '" . $statusId . "'. Valid statuses: " . $this->describeValidStatuses() . '.'
			);
		}

		return $row;
	}

	private function resolveStatusByName($rawName): array
	{
		$name = $this->validateRequiredString($rawName, 'status_name');
		if ($this->sameText($name, 'Deleted')) {
			throw new InvalidArgumentException(
				"Status 'Deleted' (" . self::STATUS_DELETED . ') cannot be assigned directly. Use DELETE /v2/shuttlebay/:id instead.'
			);
		}
		$row = $this->findStatusByName($name);
		if ($row === null) {
			throw new InvalidArgumentException(
				"Unknown status_name '" . $name . "'. Valid statuses: " . $this->describeValidStatuses() . '.'
			);
		}

		return $row;
	}

	private function resolveAssigneeForCreate(array $data): ?int
	{
		$hasId   = $this->hasValue($data, 'assigned_to_id');
		$hasName = $this->hasValue($data, 'assigned_to_name');

		if (! $hasId && ! $hasName) {
			return null;
		}

		if ($hasId) {
			$charId = $this->parsePositiveInt($data['assigned_to_id']);
			if ($charId === null) {
				throw new InvalidArgumentException("Field 'assigned_to_id' must be a positive integer or null.");
			}
			$char = $this->findCharacterById($charId);
			if ($char === null) {
				throw new InvalidArgumentException("Unknown assigned_to_id '" . $charId . "'.");
			}
			if ($hasName) {
				$name = $this->validateRequiredString($data['assigned_to_name'], 'assigned_to_name');
				if (! $this->sameText($name, (string) $char['character_name'])) {
					throw new InvalidArgumentException(
						"Conflicting 'assigned_to_id' (" . $charId . ") and 'assigned_to_name' ('" . $name . "')."
					);
				}
			}
			return (int) $char['characterID'];
		}

		return $this->resolveCharacterIdByName($data['assigned_to_name']);
	}

	private function resolveAssigneeForUpdate(array $data, array $existing): ?int
	{
		$hasIdKey   = array_key_exists('assigned_to_id', $data);
		$hasNameKey = array_key_exists('assigned_to_name', $data);

		$existingId   = $existing['assigned_to_id'] !== null ? (int) $existing['assigned_to_id'] : null;
		$existingName = $existing['assigned_to_name'] !== null ? (string) $existing['assigned_to_name'] : null;

		if (! $hasIdKey && ! $hasNameKey) {
			return $existingId;
		}

		$idValue   = $hasIdKey ? $data['assigned_to_id'] : null;
		$nameValue = $hasNameKey ? $data['assigned_to_name'] : null;
		$idCleared   = $hasIdKey && ($idValue === null || (is_string($idValue) && trim($idValue) === ''));
		$nameCleared = $hasNameKey && ($nameValue === null || (is_string($nameValue) && trim($nameValue) === ''));

		$idChanged = false;
		$newId     = null;
		if ($hasIdKey && ! $idCleared) {
			$newId = $this->parsePositiveInt($idValue);
			if ($newId === null) {
				throw new InvalidArgumentException("Field 'assigned_to_id' must be a positive integer or null.");
			}
			$idChanged = ($newId !== $existingId);
		} elseif ($idCleared && $existingId !== null) {
			$idChanged = true;
		}

		$nameChanged = false;
		$newName     = null;
		if ($hasNameKey && ! $nameCleared) {
			$newName     = $this->validateRequiredString($nameValue, 'assigned_to_name');
			$nameChanged = ($existingName === null) || ! $this->sameText($newName, $existingName);
		} elseif ($nameCleared && $existingName !== null) {
			$nameChanged = true;
		}

		if ($idChanged && $nameChanged) {
			if ($idCleared && $nameCleared) {
				return null;
			}
			if ($idCleared && $newName !== null) {
				return $this->resolveCharacterIdByName($newName);
			}
			if ($nameCleared && $newId !== null) {
				$char = $this->findCharacterById($newId);
				if ($char === null) {
					throw new InvalidArgumentException("Unknown assigned_to_id '" . $newId . "'.");
				}
				return (int) $char['characterID'];
			}
			$char = $this->findCharacterById($newId);
			if ($char === null) {
				throw new InvalidArgumentException("Unknown assigned_to_id '" . $newId . "'.");
			}
			if (! $this->sameText($newName, (string) $char['character_name'])) {
				throw new InvalidArgumentException(
					"Conflicting 'assigned_to_id' (" . $newId . ") and 'assigned_to_name' ('" . $newName . "')."
				);
			}
			return (int) $char['characterID'];
		}

		if ($idChanged) {
			if ($idCleared) {
				return null;
			}
			$char = $this->findCharacterById($newId);
			if ($char === null) {
				throw new InvalidArgumentException("Unknown assigned_to_id '" . $newId . "'.");
			}
			return (int) $char['characterID'];
		}

		if ($nameChanged) {
			if ($nameCleared) {
				return null;
			}
			return $this->resolveCharacterIdByName($newName);
		}

		return $existingId;
	}

	private function resolveCharacterIdByName($rawName): int
	{
		$name = $this->validateRequiredString($rawName, 'assigned_to_name');
		$stmt = $this->conn->prepare(
			'SELECT characterID, character_name FROM ecc_characters WHERE LOWER(TRIM(character_name)) = LOWER(?)'
		);
		$stmt->execute([$name]);
		$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if (count($matches) === 0) {
			throw new InvalidArgumentException("Unknown character assigned_to_name '" . $name . "'.");
		}
		if (count($matches) > 1) {
			throw new InvalidArgumentException(
				"Multiple characters match assigned_to_name '" . $name . "'. Please provide 'assigned_to_id'."
			);
		}

		return (int) $matches[0]['characterID'];
	}

	private function parseOperable($value): int
	{
		$parsed = $this->parseSignedInt($value);
		if ($parsed === null || ! in_array($parsed, [-1, 0, 1], true)) {
			throw new InvalidArgumentException("Field 'operable' must be '1', '0', or '-1'.");
		}

		return $parsed;
	}

	private function parsePositiveInt($value): ?int
	{
		$parsed = $this->parseSignedInt($value);
		if ($parsed === null || $parsed <= 0) {
			return null;
		}

		return $parsed;
	}

	private function parseSignedInt($value): ?int
	{
		if (is_int($value)) {
			return ($value >= -2147483648 && $value <= 2147483647) ? $value : null;
		}
		if (is_string($value)) {
			$trimmed = trim($value);
			if (preg_match('/^-?\d+$/', $trimmed) === 1) {
				$parsed = filter_var($trimmed, FILTER_VALIDATE_INT);
				if ($parsed !== false && $parsed >= -2147483648 && $parsed <= 2147483647) {
					return (int) $parsed;
				}
			}
		}

		return null;
	}

	private function sameText(string $a, string $b): bool
	{
		$ta = trim($a);
		$tb = trim($b);
		if (function_exists('mb_strtolower')) {
			return mb_strtolower($ta, 'UTF-8') === mb_strtolower($tb, 'UTF-8');
		}

		return strtolower($ta) === strtolower($tb);
	}

	private function findClassByName(string $name): ?array
	{
		$stmt = $this->conn->prepare('SELECT id, name, `type`, capacity, required_skill FROM esb_shuttle_classes WHERE LOWER(TRIM(name)) = LOWER(?) LIMIT 1');
		$stmt->execute([$name]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row === false ? null : $row;
	}

	private function findClassById(int $id): ?array
	{
		$stmt = $this->conn->prepare('SELECT id, name, `type`, capacity, required_skill FROM esb_shuttle_classes WHERE id = ? LIMIT 1');
		$stmt->execute([$id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row === false ? null : $row;
	}

	private function listClassNames(): array
	{
		$stmt = $this->conn->query('SELECT name FROM esb_shuttle_classes ORDER BY id ASC');
		return $stmt->fetchAll(PDO::FETCH_COLUMN);
	}

	private function findBaseByName(string $name): ?array
	{
		$stmt = $this->conn->prepare('SELECT id, name FROM esb_shuttle_bases WHERE LOWER(TRIM(name)) = LOWER(?) LIMIT 1');
		$stmt->execute([$name]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row === false ? null : $row;
	}

	private function findBaseById(int $id): ?array
	{
		$stmt = $this->conn->prepare('SELECT id, name FROM esb_shuttle_bases WHERE id = ? LIMIT 1');
		$stmt->execute([$id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row === false ? null : $row;
	}

	private function findDefaultBaseId(): ?int
	{
		$preferred = $this->findBaseById(self::DEFAULT_BASE_ID);
		if ($preferred !== null) {
			return (int) $preferred['id'];
		}
		$stmt = $this->conn->query('SELECT id FROM esb_shuttle_bases ORDER BY id ASC LIMIT 1');
		$id   = $stmt->fetchColumn();

		return $id === false ? null : (int) $id;
	}

	private function listBaseNames(): array
	{
		$stmt = $this->conn->query('SELECT name FROM esb_shuttle_bases ORDER BY id ASC');
		return $stmt->fetchAll(PDO::FETCH_COLUMN);
	}

	private function findConditionByName(string $name): ?array
	{
		$stmt = $this->conn->prepare('SELECT id, name, operable FROM esb_shuttle_conditions WHERE LOWER(TRIM(name)) = LOWER(?) LIMIT 1');
		$stmt->execute([$name]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row === false ? null : $row;
	}

	private function findDefaultConditionForOperable(int $operable): ?array
	{
		if ($operable === 1) {
			$stmt = $this->conn->prepare('SELECT id, name, operable FROM esb_shuttle_conditions WHERE id = ? AND operable = 1 LIMIT 1');
			$stmt->execute([self::DEFAULT_CONDITION_ID]);
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if ($row !== false) {
				return $row;
			}
		}

		$stmt = $this->conn->prepare(
			'SELECT id, name, operable FROM esb_shuttle_conditions WHERE operable = ? ORDER BY (id > 0) DESC, id ASC LIMIT 1'
		);
		$stmt->execute([$operable]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row === false ? null : $row;
	}

	private function findDefaultConditionId(): ?int
	{
		$stmt = $this->conn->prepare('SELECT id FROM esb_shuttle_conditions WHERE id = ? LIMIT 1');
		$stmt->execute([self::DEFAULT_CONDITION_ID]);
		$id = $stmt->fetchColumn();
		if ($id !== false) {
			return (int) $id;
		}
		$fallback = $this->conn->query('SELECT id FROM esb_shuttle_conditions ORDER BY (id > 0) DESC, id ASC LIMIT 1')->fetchColumn();

		return $fallback === false ? null : (int) $fallback;
	}

	private function listConditionNames(): array
	{
		$stmt = $this->conn->query('SELECT name FROM esb_shuttle_conditions ORDER BY id ASC');
		return $stmt->fetchAll(PDO::FETCH_COLUMN);
	}

	private function findStatusById(int $id): ?array
	{
		$stmt = $this->conn->prepare('SELECT id, `status` FROM esb_shuttle_status WHERE id = ? AND id != ? LIMIT 1');
		$stmt->execute([$id, self::STATUS_DELETED]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row === false ? null : $row;
	}

	private function findStatusByName(string $name): ?array
	{
		$stmt = $this->conn->prepare('SELECT id, `status` FROM esb_shuttle_status WHERE LOWER(TRIM(`status`)) = LOWER(?) AND id != ? LIMIT 1');
		$stmt->execute([$name, self::STATUS_DELETED]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row === false ? null : $row;
	}

	private function describeValidStatuses(): string
	{
		$stmt = $this->conn->prepare('SELECT id, `status` FROM esb_shuttle_status WHERE id != ? ORDER BY id ASC');
		$stmt->execute([self::STATUS_DELETED]);
		$rows  = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$parts = [];
		foreach ($rows as $row) {
			$parts[] = $row['id'] . ' (' . $row['status'] . ')';
		}

		return implode(', ', $parts);
	}

	private function findCharacterById(int $id): ?array
	{
		$stmt = $this->conn->prepare('SELECT characterID, character_name FROM ecc_characters WHERE characterID = ? LIMIT 1');
		$stmt->execute([$id]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		return $row === false ? null : $row;
	}
}
