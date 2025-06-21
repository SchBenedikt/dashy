<?php

declare(strict_types=1);

namespace OCA\Dashy\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\App\IAppManager;

class ApiController extends Controller {
	private IConfig $config;
	private IDBConnection $db;
	private IUserSession $userSession;
	private IAppManager $appManager;

	public function __construct(
		string $appName,
		IRequest $request,
		IConfig $config,
		IDBConnection $db,
		IUserSession $userSession,
		IAppManager $appManager
	) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->db = $db;
		$this->userSession = $userSession;
		$this->appManager = $appManager;
	}

	private function getUserId(): string {
		$user = $this->userSession->getUser();
		return $user ? $user->getUID() : '';
	}

	/**
	 * Get dashboard configuration
	 *
	 * @return DataResponse<Http::STATUS_OK, array{layout: array, widgets: array}, array{}>
	 *
	 * 200: Dashboard configuration returned
	 */
	#[NoAdminRequired]
	public function getDashboard(): DataResponse {
		$userId = $this->getUserId();
		if (empty($userId)) {
			return new DataResponse(['layout' => [], 'widgets' => []], Http::STATUS_UNAUTHORIZED);
		}

		$layout = $this->config->getUserValue($userId, 'dashy', 'layout', '[]');
		$widgets = $this->config->getUserValue($userId, 'dashy', 'widgets', '[]');

		return new DataResponse([
			'layout' => json_decode($layout, true) ?: [],
			'widgets' => json_decode($widgets, true) ?: [],
		]);
	}

	/**
	 * Save user's dashboard configuration
	 *
	 * @return DataResponse<Http::STATUS_OK, array{success: bool}, array{}>
	 *
	 * 200: Dashboard configuration saved
	 */
	#[NoAdminRequired]
	public function saveDashboard(): DataResponse {
		$userId = $this->getUserId();
		if (empty($userId)) {
			return new DataResponse(['success' => false, 'error' => 'User not authenticated'], Http::STATUS_UNAUTHORIZED);
		}

		$layout = $this->request->getParam('layout', []);
		$widgets = $this->request->getParam('widgets', []);

		try {
			$this->config->setUserValue(
				$userId,
				'dashy',
				'layout',
				json_encode($layout)
			);

			$this->config->setUserValue(
				$userId,
				'dashy',
				'widgets',
				json_encode($widgets)
			);

			return new DataResponse(['success' => true]);
		} catch (\Exception $e) {
			return new DataResponse(['success' => false, 'error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Get calendar events
	 *
	 * @return DataResponse<Http::STATUS_OK, array{events: array}, array{}>
	 *
	 * 200: Calendar events returned
	 */
	#[NoAdminRequired]
	public function getCalendarEvents(): DataResponse {
		$userId = $this->getUserId();
		if (empty($userId)) {
			return new DataResponse(['events' => []]);
		}

		try {
			// Get calendar events from the database
			$qb = $this->db->getQueryBuilder();
			
			// Get calendars for this user
			$calendarsQuery = $qb->select('id', 'displayname')
				->from('calendars')
				->where($qb->expr()->eq('principaluri', $qb->createNamedParameter('principals/users/' . $userId)));
			
			$calendarsResult = $calendarsQuery->executeQuery();
			$calendarIds = [];
			
			while ($calendar = $calendarsResult->fetch()) {
				$calendarIds[] = (int)$calendar['id'];
			}
			$calendarsResult->closeCursor();
			
			if (empty($calendarIds)) {
				return new DataResponse(['events' => []]);
			}
			
			// Get calendar events  
			$qb = $this->db->getQueryBuilder();
			$eventsQuery = $qb->select('co.uri', 'co.calendardata', 'c.displayname as calendar_name')
				->from('calendarobjects', 'co')
				->leftJoin('co', 'calendars', 'c', 'co.calendarid = c.id')
				->where($qb->expr()->in('co.calendarid', $qb->createNamedParameter($calendarIds, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)))
				->andWhere($qb->expr()->eq('co.componenttype', $qb->createNamedParameter('VEVENT')))
				->setMaxResults(10);
			
			$eventsResult = $eventsQuery->executeQuery();
			$events = [];
			
			while ($row = $eventsResult->fetch()) {
				try {
					$calendarData = $row['calendardata'];
					if (empty($calendarData)) {
						continue;
					}
					
					// Basic iCal parsing to extract event info
					$eventInfo = $this->parseICalEvent($calendarData);
					if ($eventInfo) {
						$eventInfo['calendar'] = $row['calendar_name'] ?? 'Calendar';
						$events[] = $eventInfo;
					}
				} catch (\Exception $e) {
					// Skip this event if parsing fails
					continue;
				}
			}
			$eventsResult->closeCursor();
			
			// Sort events by start date
			if (!empty($events)) {
				usort($events, function($a, $b) {
					return strtotime($a['start']) - strtotime($b['start']);
				});
			}
			
			return new DataResponse(['events' => $events]);
			
		} catch (\Exception $e) {
			// Return mock data as fallback
			$events = [
				[
					'id' => '1',
					'title' => 'Mock Event - Error: ' . $e->getMessage(),
					'start' => date('c', strtotime('+2 hours')),
					'location' => '',
					'calendar' => 'Debug',
				],
			];
			return new DataResponse(['events' => $events]);
		}
	}
	
	/**
	 * Parse basic information from iCal event data
	 */
	private function parseICalEvent(string $calendarData): ?array {
		$lines = explode("\n", $calendarData);
		$event = [];
		$inEvent = false;
		
		foreach ($lines as $line) {
			$line = trim($line);
			
			if ($line === 'BEGIN:VEVENT') {
				$inEvent = true;
				continue;
			}
			
			if ($line === 'END:VEVENT') {
				break;
			}
			
			if (!$inEvent) {
				continue;
			}
			
			if (strpos($line, ':') === false) {
				continue;
			}
			
			list($key, $value) = explode(':', $line, 2);
			
			// Handle key parameters (e.g., DTSTART;TZID=...)
			if (strpos($key, ';') !== false) {
				$key = explode(';', $key)[0];
			}
			
			switch ($key) {
				case 'UID':
					$event['id'] = $value;
					break;
				case 'SUMMARY':
					$event['title'] = $value;
					break;
				case 'DTSTART':
					$event['start'] = $this->parseICalDateTime($value);
					break;
				case 'LOCATION':
					$event['location'] = $value;
					break;
			}
		}
		
		// Only return event if we have minimum required fields
		if (isset($event['title']) && isset($event['start'])) {
			$event['id'] = $event['id'] ?? 'event_' . time();
			$event['location'] = $event['location'] ?? '';
			return $event;
		}
		
		return null;
	}
	
	/**
	 * Parse iCal datetime format
	 */
	private function parseICalDateTime(string $datetime): string {
		try {
			// Remove TZID and other parameters, keep only the date part
			if (strpos($datetime, 'T') !== false) {
				// DateTime format: 20241220T140000Z or 20241220T140000
				$datetime = preg_replace('/[TZ]/', ' ', $datetime);
				$dt = \DateTime::createFromFormat('Ymd His', $datetime);
			} else {
				// Date only format: 20241220
				$dt = \DateTime::createFromFormat('Ymd', $datetime);
			}
			
			if ($dt) {
				return $dt->format('c'); // ISO 8601 format
			}
		} catch (\Exception $e) {
			// Fallback
		}
		
		// Fallback to current time if parsing fails
		return date('c');
	}

	/**
	 * Get tasks from Tasks app (CalDAV VTODO)
	 *
	 * @return DataResponse<Http::STATUS_OK, array{tasks: array}, array{}>
	 *
	 * 200: Tasks returned
	 */
	#[NoAdminRequired]
	public function getTasks(): DataResponse {
		$userId = $this->getUserId();
		if (empty($userId)) {
			return new DataResponse(['tasks' => []]);
		}

		try {
			// Check if Tasks app is enabled
			$taskAppExists = $this->appManager->isEnabledForUser('tasks', $this->userSession->getUser());
			if (!$taskAppExists) {
				return new DataResponse(['tasks' => [], 'message' => 'Tasks app not enabled']);
			}

			// Query CalDAV for VTODO objects (tasks)
			$qb = $this->db->getQueryBuilder();
			$query = $qb->select('co.uri', 'co.calendardata', 'c.displayname as calendar_name', 'c.id as calendar_id')
				->from('calendarobjects', 'co')
				->leftJoin('co', 'calendars', 'c', 'co.calendarid = c.id')
				->where($qb->expr()->eq('c.principaluri', $qb->createNamedParameter('principals/users/' . $userId)))
				->andWhere($qb->expr()->eq('co.componenttype', $qb->createNamedParameter('VTODO')))
				->orderBy('c.displayname')
				->addOrderBy('co.uri');
			
			$result = $query->executeQuery();
			$tasks = [];
			
			while ($row = $result->fetch()) {
				$task = $this->parseICalTask($row['calendardata']);
				if ($task) {
					$task['list'] = $row['calendar_name'] ?? 'Tasks';
					$task['listId'] = $row['calendar_id'];
					$tasks[] = $task;
				}
			}
			$result->closeCursor();
			
			return new DataResponse(['tasks' => $tasks]);
			
		} catch (\Exception $e) {
			// Return error info for debugging
			return new DataResponse([
				'tasks' => [],
				'error' => 'Exception occurred: ' . $e->getMessage(),
				'trace' => $e->getTraceAsString()
			], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
	
	/**
	 * Parse basic information from iCal task data
	 */
	private function parseICalTask(string $calendarData): ?array {
		$lines = explode("\n", $calendarData);
		$task = [
			'completed' => false,
			'priority' => 0,
			'due' => null,
			'description' => '',
			'completedAt' => null,
		];
		$inTask = false;
		
		foreach ($lines as $line) {
			$line = trim($line);
			
			if ($line === 'BEGIN:VTODO') {
				$inTask = true;
				continue;
			}
			
			if ($line === 'END:VTODO') {
				break;
			}
			
			if (!$inTask) {
				continue;
			}
			
			if (strpos($line, ':') === false) {
				continue;
			}
			
			list($key, $value) = explode(':', $line, 2);
			
			// Handle key parameters (e.g., DTSTART;TZID=...)
			if (strpos($key, ';') !== false) {
				$key = explode(';', $key)[0];
			}
			
			switch ($key) {
				case 'UID':
					$task['id'] = $value;
					break;
				case 'SUMMARY':
					$task['summary'] = $value;
					break;
				case 'DESCRIPTION':
					$task['description'] = $value;
					break;
				case 'DUE':
					$task['due'] = $this->parseICalDateTime($value);
					break;
				case 'PRIORITY':
					// iCal priority: 1-9 (1=highest, 9=lowest), 0=undefined
					$priority = (int)$value;
					if ($priority > 0) {
						// Convert to our scale: 1-9 (9=highest, 1=lowest)
						$task['priority'] = 10 - $priority;
					}
					break;
				case 'STATUS':
					$task['completed'] = strtoupper($value) === 'COMPLETED';
					break;
				case 'COMPLETED':
					$task['completed'] = true;
					$task['completedAt'] = $this->parseICalDateTime($value);
					break;
				case 'PERCENT-COMPLETE':
					$percent = (int)$value;
					$task['completed'] = $percent >= 100;
					break;
			}
		}
		
		// Only return task if we have minimum required fields
		if (isset($task['summary'])) {
			$task['id'] = $task['id'] ?? 'task_' . time();
			return $task;
		}
		
		return null;
	}

	/**
	 * Update task completion status
	 *
	 * @return DataResponse<Http::STATUS_OK, array{success: bool}, array{}>
	 *
	 * 200: Task updated
	 */
	#[NoAdminRequired]
	public function updateTask(string $taskId): DataResponse {
		$userId = $this->getUserId();
		if (empty($userId)) {
			return new DataResponse(['success' => false, 'error' => 'User not authenticated'], Http::STATUS_UNAUTHORIZED);
		}

		$completed = $this->request->getParam('completed', false);

		try {
			// Find the task in the database
			$qb = $this->db->getQueryBuilder();
			$query = $qb->select('co.uri', 'co.calendardata', 'co.calendarid', 'co.id as object_id')
				->from('calendarobjects', 'co')
				->leftJoin('co', 'calendars', 'c', 'co.calendarid = c.id')
				->where($qb->expr()->eq('c.principaluri', $qb->createNamedParameter('principals/users/' . $userId)))
				->andWhere($qb->expr()->eq('co.componenttype', $qb->createNamedParameter('VTODO')))
				->andWhere($qb->expr()->like('co.calendardata', $qb->createNamedParameter('%UID:' . $taskId . '%')));
			
			$result = $query->executeQuery();
			$row = $result->fetch();
			$result->closeCursor();
			
			if (!$row) {
				return new DataResponse(['success' => false, 'error' => 'Task not found'], Http::STATUS_NOT_FOUND);
			}
			
			// Update the calendar data
			$calendarData = $row['calendardata'];
			$updatedData = $this->updateICalTaskCompletion($calendarData, $completed);
			
			if ($updatedData) {
				// Update in database
				$updateQuery = $qb->update('calendarobjects')
					->set('calendardata', $qb->createNamedParameter($updatedData))
					->set('lastmodified', $qb->createNamedParameter(time()))
					->where($qb->expr()->eq('id', $qb->createNamedParameter($row['object_id'])));
				
				$updateQuery->executeStatement();
				
				return new DataResponse(['success' => true]);
			}
			
			return new DataResponse(['success' => false, 'error' => 'Failed to update task'], Http::STATUS_INTERNAL_SERVER_ERROR);
			
		} catch (\Exception $e) {
			return new DataResponse(['success' => false, 'error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}
	
	/**
	 * Update task completion status in iCal data
	 */
	private function updateICalTaskCompletion(string $calendarData, bool $completed): ?string {
		$lines = explode("\n", $calendarData);
		$updatedLines = [];
		$inTask = false;
		$hasStatus = false;
		$hasCompleted = false;
		
		foreach ($lines as $line) {
			$trimmedLine = trim($line);
			
			if ($trimmedLine === 'BEGIN:VTODO') {
				$inTask = true;
				$updatedLines[] = $line;
				continue;
			}
			
			if ($trimmedLine === 'END:VTODO') {
				// Add missing properties before end
				if ($inTask && $completed) {
					if (!$hasStatus) {
						$updatedLines[] = 'STATUS:COMPLETED';
					}
					if (!$hasCompleted) {
						$updatedLines[] = 'COMPLETED:' . date('Ymd\THis\Z');
					}
				}
				$inTask = false;
				$updatedLines[] = $line;
				continue;
			}
			
			if (!$inTask) {
				$updatedLines[] = $line;
				continue;
			}
			
			// Handle task properties
			if (strpos($trimmedLine, 'STATUS:') === 0) {
				$hasStatus = true;
				if ($completed) {
					$updatedLines[] = 'STATUS:COMPLETED';
				} else {
					$updatedLines[] = 'STATUS:NEEDS-ACTION';
				}
				continue;
			}
			
			if (strpos($trimmedLine, 'COMPLETED:') === 0) {
				$hasCompleted = true;
				if ($completed) {
					$updatedLines[] = 'COMPLETED:' . date('Ymd\THis\Z');
				}
				// Skip line if not completed
				continue;
			}
			
			if (strpos($trimmedLine, 'PERCENT-COMPLETE:') === 0) {
				if ($completed) {
					$updatedLines[] = 'PERCENT-COMPLETE:100';
				} else {
					$updatedLines[] = 'PERCENT-COMPLETE:0';
				}
				continue;
			}
			
			$updatedLines[] = $line;
		}
		
		return implode("\n", $updatedLines);
	}

	/**
	 * Get weather information
	 *
	 * @return DataResponse<Http::STATUS_OK, array{weather: array}, array{}>
	 *
	 * 200: Weather information returned
	 */
	#[NoAdminRequired]
	public function getWeather(): DataResponse {
		// Return mock weather data for now
		$weather = [
			'temperature' => 22,
			'condition' => 'Sunny',
			'location' => 'Sample City',
		];

		return new DataResponse(['weather' => $weather]);
	}

	/**
	 * Get contacts information
	 *
	 * @return DataResponse<Http::STATUS_OK, array{contacts: array}, array{}>
	 *
	 * 200: Contacts information returned
	 */
	#[NoAdminRequired]
	public function getContacts(): DataResponse {
		$userId = $this->getUserId();
		if (empty($userId)) {
			return new DataResponse(['contacts' => []]);
		}

		try {
			// Check if Contacts app is enabled
			if (!$this->appManager->isEnabledForUser('contacts')) {
				return new DataResponse(['contacts' => []], Http::STATUS_NOT_FOUND);
			}

			// Get contacts from the database
			$qb = $this->db->getQueryBuilder();
			$contactsQuery = $qb->select('id', 'displayname', 'carddata')
				->from('cards')
				->leftJoin('cards', 'addressbooks', 'ab', 'cards.addressbookid = ab.id')
				->where($qb->expr()->eq('ab.principaluri', $qb->createNamedParameter('principals/users/' . $userId)))
				->orderBy('displayname', 'ASC')
				->setMaxResults(50);
			
			$contactsResult = $contactsQuery->executeQuery();
			$contacts = [];
			
			while ($contact = $contactsResult->fetch()) {
				// Parse VCard data to extract email and phone
				$cardData = $contact['carddata'];
				$email = $this->extractVCardProperty($cardData, 'EMAIL');
				$phone = $this->extractVCardProperty($cardData, 'TEL');
				
				$contacts[] = [
					'id' => $contact['id'],
					'displayName' => $contact['displayname'] ?: 'Unknown',
					'email' => $email,
					'phone' => $phone,
				];
			}
			$contactsResult->closeCursor();
			
			return new DataResponse(['contacts' => $contacts]);
			
		} catch (\Exception $e) {
			return new DataResponse(['contacts' => []], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Get files information
	 *
	 * @return DataResponse<Http::STATUS_OK, array{files: array}, array{}>
	 *
	 * 200: Files information returned
	 */
	#[NoAdminRequired]
	public function getFiles(): DataResponse {
		$userId = $this->getUserId();
		if (empty($userId)) {
			return new DataResponse(['files' => []]);
		}

		try {
			// Get recent files from the database
			$qb = $this->db->getQueryBuilder();
			$filesQuery = $qb->select('f.fileid', 'f.name', 'f.path', 'f.size', 'f.mtime', 'f.mimetype', 'mt.mimetype as full_mimetype')
				->from('filecache', 'f')
				->leftJoin('f', 'mimetypes', 'mt', 'f.mimetype = mt.id')
				->leftJoin('f', 'storages', 's', 'f.storage = s.numeric_id')
				->where($qb->expr()->like('s.id', $qb->createNamedParameter('home::' . $userId . '%')))
				->andWhere($qb->expr()->neq('f.mimetype', $qb->createNamedParameter(2))) // Exclude directories (mimetype 2 = httpd/unix-directory)
				->orderBy('f.mtime', 'DESC')
				->setMaxResults(30);
			
			$filesResult = $filesQuery->executeQuery();
			$files = [];
			
			while ($file = $filesResult->fetch()) {
				$files[] = [
					'id' => $file['fileid'],
					'name' => $file['name'],
					'path' => dirname($file['path']),
					'size' => (int)$file['size'],
					'mtime' => (int)$file['mtime'],
					'mimetype' => $file['full_mimetype'] ?: '',
					'type' => 'file',
				];
			}
			$filesResult->closeCursor();
			
			return new DataResponse(['files' => $files]);
			
		} catch (\Exception $e) {
			return new DataResponse(['files' => []], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Get notes information
	 *
	 * @return DataResponse<Http::STATUS_OK, array{notes: array}, array{}>
	 *
	 * 200: Notes information returned
	 */
	#[NoAdminRequired]
	public function getNotes(): DataResponse {
		$userId = $this->getUserId();
		if (empty($userId)) {
			return new DataResponse(['notes' => []]);
		}

		$folder = $this->request->getParam('folder', '');

		try {
			// Always use fallback for now to avoid database issues
			$notesJson = $this->config->getUserValue($userId, 'dashy', 'simple_notes', '[]');
			$notes = json_decode($notesJson, true) ?: [];
			
			// Filter by folder if specified
			if (!empty($folder)) {
				$notes = array_filter($notes, function($note) use ($folder) {
					return isset($note['folder']) && $note['folder'] === $folder;
				});
			}
			
			// Add some dummy notes if none exist for testing
			if (empty($notes)) {
				$notes = [
					[
						'id' => '1',
						'title' => 'Welcome Note',
						'content' => 'This is a test note from the Dashy Notes widget.',
						'modified' => time(),
						'category' => '',
						'folder' => $folder,
					],
					[
						'id' => '2',
						'title' => 'Another Note',
						'content' => 'This is another test note.',
						'modified' => time() - 3600,
						'category' => 'Personal',
						'folder' => 'Personal',
					]
				];
				
				// Filter dummy notes by folder too
				if (!empty($folder)) {
					$notes = array_filter($notes, function($note) use ($folder) {
						return $note['folder'] === $folder;
					});
				}
			}
			
			return new DataResponse(['notes' => array_values($notes)]);
			
		} catch (\Exception $e) {
			error_log('Dashy getNotes error: ' . $e->getMessage());
			error_log('Dashy getNotes trace: ' . $e->getTraceAsString());
			
			// Return dummy data on error
			return new DataResponse(['notes' => [
				[
					'id' => 'error',
					'title' => 'Error Note',
					'content' => 'There was an error loading notes: ' . $e->getMessage(),
					'modified' => time(),
					'category' => '',
					'folder' => '',
				]
			]]);
		}
	}

	/**
	 * Get available folders for notes storage
	 *
	 * @return DataResponse<Http::STATUS_OK, array{folders: array}, array{}>
	 *
	 * 200: Available folders returned
	 */
	#[NoAdminRequired]
	public function getNotesFolders(): DataResponse {
		$userId = $this->getUserId();
		if (empty($userId)) {
			return new DataResponse(['folders' => []]);
		}

		try {
			$folders = [];
			
			// Add root folder
			$folders[] = [
				'path' => '',
				'name' => 'Root folder',
				'type' => 'folder'
			];

			// Try to get real folders from Nextcloud Files
			try {
				$qb = $this->db->getQueryBuilder();
				$foldersQuery = $qb->select('f.name', 'f.path')
					->from('filecache', 'f')
					->leftJoin('f', 'storages', 's', 'f.storage = s.numeric_id')
					->where($qb->expr()->like('s.id', $qb->createNamedParameter('home::' . $userId . '%')))
					->andWhere($qb->expr()->eq('f.mimetype', $qb->createNamedParameter(2))) // Directory mimetype
					->andWhere($qb->expr()->neq('f.name', $qb->createNamedParameter('.')))
					->andWhere($qb->expr()->neq('f.name', $qb->createNamedParameter('..')))
					->andWhere($qb->expr()->notLike('f.name', $qb->createNamedParameter('.%'))) // Skip hidden folders
					->orderBy('f.path', 'ASC')
					->setMaxResults(100);

				$foldersResult = $foldersQuery->executeQuery();
				
				while ($folder = $foldersResult->fetch()) {
					// Clean up the path - remove /files/ prefix and user directory
					$cleanPath = $folder['path'];
					$cleanPath = preg_replace('#^/?files/?#', '', $cleanPath);
					$cleanPath = preg_replace('#^' . preg_quote($userId) . '/?files/?#', '', $cleanPath);
					$cleanPath = ltrim($cleanPath, '/');
					
					if (!empty($cleanPath) && !empty($folder['name'])) {
						$folders[] = [
							'path' => $cleanPath,
							'name' => $folder['name'],
							'type' => 'folder'
						];
					}
				}
				$foldersResult->closeCursor();
				
			} catch (\Exception $e) {
				error_log('Failed to fetch real folders: ' . $e->getMessage());
			}

			// If no real folders found, add some common suggestions
			if (count($folders) === 1) {
				$folders[] = ['path' => 'Notes', 'name' => 'Notes', 'type' => 'suggestion'];
				$folders[] = ['path' => 'Documents', 'name' => 'Documents', 'type' => 'suggestion'];
				$folders[] = ['path' => 'Documents/Notes', 'name' => 'Documents/Notes', 'type' => 'suggestion'];
				$folders[] = ['path' => 'Personal', 'name' => 'Personal', 'type' => 'suggestion'];
				$folders[] = ['path' => 'Work', 'name' => 'Work', 'type' => 'suggestion'];
			}

			return new DataResponse(['folders' => $folders]);
			
		} catch (\Exception $e) {
			error_log('Dashy getNotesFolders error: ' . $e->getMessage());
			
			// Fallback to common folder names
			$folders = [
				['path' => '', 'name' => 'Root folder', 'type' => 'folder'],
				['path' => 'Notes', 'name' => 'Notes', 'type' => 'suggestion'],
				['path' => 'Documents', 'name' => 'Documents', 'type' => 'suggestion'],
				['path' => 'Personal', 'name' => 'Personal', 'type' => 'suggestion'],
				['path' => 'Work', 'name' => 'Work', 'type' => 'suggestion'],
			];

			return new DataResponse(['folders' => $folders]);
		}
	}

	/**
	 * Create a new note
	 *
	 * @param string $title Note title
	 * @param string $content Note content
	 * @return DataResponse<Http::STATUS_OK, array{success: bool, note?: array}, array{}>
	 *
	 * 200: Note created successfully
	 */
	#[NoAdminRequired]
	public function createNote(string $title, string $content): DataResponse {
		$userId = $this->getUserId();
		if (empty($userId)) {
			return new DataResponse(['success' => false]);
		}

		$folder = $this->request->getParam('folder', '');

		try {
			$noteData = [
				'id' => uniqid(),
				'title' => $title ?: 'Untitled',
				'content' => $content,
				'modified' => time(),
				'category' => $folder, // Use folder as category
				'folder' => $folder,
			];

			// Check if Notes app is enabled
			if ($this->appManager->isEnabledForUser('notes')) {
				// Save to Notes app database
				$qb = $this->db->getQueryBuilder();
				$qb->insert('notes_notes')
					->values([
						'user_id' => $qb->createNamedParameter($userId),
						'title' => $qb->createNamedParameter($noteData['title']),
						'content' => $qb->createNamedParameter($noteData['content']),
						'modified' => $qb->createNamedParameter($noteData['modified']),
						'category' => $qb->createNamedParameter($noteData['category']),
					]);
				$qb->executeStatement();
				$noteData['id'] = $this->db->lastInsertId();
			} else {
				// Fallback: store in app config
				$notesJson = $this->config->getUserValue($userId, 'dashy', 'simple_notes', '[]');
				$notes = json_decode($notesJson, true) ?: [];
				$notes[] = $noteData;
				$this->config->setUserValue($userId, 'dashy', 'simple_notes', json_encode($notes));
			}

			return new DataResponse(['success' => true, 'note' => $noteData]);
			
		} catch (\Exception $e) {
			return new DataResponse(['success' => false], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Update an existing note
	 *
	 * @param string $noteId Note ID
	 * @param string $title Note title
	 * @param string $content Note content
	 * @return DataResponse<Http::STATUS_OK, array{success: bool, note?: array}, array{}>
	 *
	 * 200: Note updated successfully
	 */
	#[NoAdminRequired]
	public function updateNote(string $noteId, string $title, string $content): DataResponse {
		$userId = $this->getUserId();
		if (empty($userId)) {
			return new DataResponse(['success' => false]);
		}

		try {
			$noteData = [
				'id' => $noteId,
				'title' => $title ?: 'Untitled',
				'content' => $content,
				'modified' => time(),
			];

			// Check if Notes app is enabled
			if ($this->appManager->isEnabledForUser('notes')) {
				// Update in Notes app database
				$qb = $this->db->getQueryBuilder();
				$qb->update('notes_notes')
					->set('title', $qb->createNamedParameter($noteData['title']))
					->set('content', $qb->createNamedParameter($noteData['content']))
					->set('modified', $qb->createNamedParameter($noteData['modified']))
					->where($qb->expr()->eq('id', $qb->createNamedParameter($noteId)))
					->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
				$qb->executeStatement();
			} else {
				// Fallback: update in app config
				$notesJson = $this->config->getUserValue($userId, 'dashy', 'simple_notes', '[]');
				$notes = json_decode($notesJson, true) ?: [];
				
				foreach ($notes as &$note) {
					if ($note['id'] === $noteId) {
						$note['title'] = $noteData['title'];
						$note['content'] = $noteData['content'];
						$note['modified'] = $noteData['modified'];
						break;
					}
				}
				
				$this->config->setUserValue($userId, 'dashy', 'simple_notes', json_encode($notes));
			}

			return new DataResponse(['success' => true, 'note' => $noteData]);
			
		} catch (\Exception $e) {
			return new DataResponse(['success' => false], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Extract a property from VCard data
	 *
	 * @param string $cardData VCard data
	 * @param string $property Property name (e.g., 'EMAIL', 'TEL')
	 * @return string|null Property value or null if not found
	 */
	private function extractVCardProperty(string $cardData, string $property): ?string {
		$lines = explode("\n", $cardData);
		foreach ($lines as $line) {
			$line = trim($line);
			if (stripos($line, $property . ':') === 0) {
				return trim(substr($line, strlen($property) + 1));
			}
			if (stripos($line, $property . ';') === 0) {
				$colonPos = strpos($line, ':');
				if ($colonPos !== false) {
					return trim(substr($line, $colonPos + 1));
				}
			}
		}
		return null;
	}

	/**
	 * Get bookmarks information
	 *
	 * @return DataResponse<Http::STATUS_OK, array{bookmarks: array}, array{}>
	 *
	 * 200: Bookmarks information returned
	 */
	#[NoAdminRequired]
	public function getBookmarks(): DataResponse {
		$userId = $this->getUserId();
		if (empty($userId)) {
			return new DataResponse(['bookmarks' => []]);
		}

		try {
			// Check if Bookmarks app is enabled
			if ($this->appManager->isEnabledForUser('bookmarks')) {
				// Get bookmarks from Bookmarks app database
				$qb = $this->db->getQueryBuilder();
				$bookmarksQuery = $qb->select('b.id', 'b.url', 'b.title', 'b.description', 'b.added', 'b.lastAccessed')
					->from('bookmarks', 'b')
					->leftJoin('b', 'bookmarks_folders', 'bf', 'b.id = bf.bookmark_id')
					->leftJoin('bf', 'bookmarks_folders_bookmarks', 'bfb', 'bf.folder_id = bfb.folder_id')
					->leftJoin('bfb', 'bookmarks_root_folders', 'brf', 'bfb.folder_id = brf.folder_id')
					->where($qb->expr()->eq('brf.user_id', $qb->createNamedParameter($userId)))
					->orderBy('b.lastAccessed', 'DESC')
					->addOrderBy('b.added', 'DESC')
					->setMaxResults(30);
				
				$bookmarksResult = $bookmarksQuery->executeQuery();
				$bookmarks = [];
				
				while ($bookmark = $bookmarksResult->fetch()) {
					$bookmarks[] = [
						'id' => $bookmark['id'],
						'url' => $bookmark['url'],
						'title' => $bookmark['title'] ?: $bookmark['url'],
						'description' => $bookmark['description'] ?: '',
						'added' => (int)$bookmark['added'],
						'lastAccessed' => (int)$bookmark['lastAccessed'],
						'favicon' => $this->getFaviconUrl($bookmark['url']),
					];
				}
				$bookmarksResult->closeCursor();
				
				return new DataResponse(['bookmarks' => $bookmarks]);
			} else {
				// Fallback: use a simple bookmarks storage in app config
				$bookmarksJson = $this->config->getUserValue($userId, 'dashy', 'simple_bookmarks', '[]');
				$bookmarks = json_decode($bookmarksJson, true) ?: [];
				
				return new DataResponse(['bookmarks' => $bookmarks]);
			}
			
		} catch (\Exception $e) {
			return new DataResponse(['bookmarks' => []], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Create a new bookmark
	 *
	 * @param string $url Bookmark URL
	 * @param string $title Bookmark title
	 * @param string $description Bookmark description
	 * @return DataResponse<Http::STATUS_OK, array{success: bool, bookmark?: array}, array{}>
	 *
	 * 200: Bookmark created successfully
	 */
	#[NoAdminRequired]
	public function createBookmark(string $url, string $title, string $description = ''): DataResponse {
		$userId = $this->getUserId();
		if (empty($userId) || empty($url)) {
			return new DataResponse(['success' => false]);
		}

		try {
			$bookmarkData = [
				'id' => uniqid(),
				'url' => $url,
				'title' => $title ?: $url,
				'description' => $description,
				'added' => time(),
				'lastAccessed' => 0,
				'favicon' => $this->getFaviconUrl($url),
			];

			// Check if Bookmarks app is enabled
			if ($this->appManager->isEnabledForUser('bookmarks')) {
				// Save to Bookmarks app database - simplified approach
				// Note: Real implementation would need to handle folders and proper structure
				$qb = $this->db->getQueryBuilder();
				$qb->insert('bookmarks')
					->values([
						'url' => $qb->createNamedParameter($bookmarkData['url']),
						'title' => $qb->createNamedParameter($bookmarkData['title']),
						'description' => $qb->createNamedParameter($bookmarkData['description']),
						'added' => $qb->createNamedParameter($bookmarkData['added']),
						'lastAccessed' => $qb->createNamedParameter($bookmarkData['lastAccessed']),
					]);
				$qb->executeStatement();
				$bookmarkData['id'] = $this->db->lastInsertId();
			} else {
				// Fallback: store in app config
				$bookmarksJson = $this->config->getUserValue($userId, 'dashy', 'simple_bookmarks', '[]');
				$bookmarks = json_decode($bookmarksJson, true) ?: [];
				$bookmarks[] = $bookmarkData;
				$this->config->setUserValue($userId, 'dashy', 'simple_bookmarks', json_encode($bookmarks));
			}

			return new DataResponse(['success' => true, 'bookmark' => $bookmarkData]);
			
		} catch (\Exception $e) {
			return new DataResponse(['success' => false], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Track bookmark access
	 *
	 * @param string $bookmarkId Bookmark ID
	 * @return DataResponse<Http::STATUS_OK, array{success: bool}, array{}>
	 *
	 * 200: Access tracked successfully
	 */
	#[NoAdminRequired]
	public function trackBookmarkAccess(string $bookmarkId): DataResponse {
		$userId = $this->getUserId();
		if (empty($userId)) {
			return new DataResponse(['success' => false]);
		}

		try {
			// Check if Bookmarks app is enabled
			if ($this->appManager->isEnabledForUser('bookmarks')) {
				// Update lastAccessed in Bookmarks app database
				$qb = $this->db->getQueryBuilder();
				$qb->update('bookmarks')
					->set('lastAccessed', $qb->createNamedParameter(time()))
					->where($qb->expr()->eq('id', $qb->createNamedParameter($bookmarkId)));
				$qb->executeStatement();
			} else {
				// Update in simple storage
				$bookmarksJson = $this->config->getUserValue($userId, 'dashy', 'simple_bookmarks', '[]');
				$bookmarks = json_decode($bookmarksJson, true) ?: [];
				
				foreach ($bookmarks as &$bookmark) {
					if ($bookmark['id'] === $bookmarkId) {
						$bookmark['lastAccessed'] = time();
						break;
					}
				}
				
				$this->config->setUserValue($userId, 'dashy', 'simple_bookmarks', json_encode($bookmarks));
			}

			return new DataResponse(['success' => true]);
			
		} catch (\Exception $e) {
			return new DataResponse(['success' => false], Http::STATUS_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * Get favicon URL for a given website URL
	 *
	 * @param string $url Website URL
	 * @return string Favicon URL
	 */
	private function getFaviconUrl(string $url): string {
		$parsedUrl = parse_url($url);
		if (!$parsedUrl || !isset($parsedUrl['host'])) {
			return '';
		}
		
		$scheme = $parsedUrl['scheme'] ?? 'https';
		$host = $parsedUrl['host'];
		
		return "{$scheme}://{$host}/favicon.ico";
	}

	/**
	 * Browse folders in a specific directory
	 *
	 * @param string $path The path to browse
	 * @return DataResponse<Http::STATUS_OK, array{folders: array, currentPath: string, parentPath: string|null}, array{}>
	 *
	 * 200: Folder contents returned
	 */
	#[NoAdminRequired]
	public function browseFolders(string $path = ''): DataResponse {
		$userId = $this->getUserId();
		if (empty($userId)) {
			return new DataResponse(['folders' => [], 'currentPath' => '', 'parentPath' => null]);
		}

		try {
			$folders = [];
			$cleanPath = trim($path, '/');
			
			// Determine parent path
			$parentPath = null;
			if (!empty($cleanPath)) {
				$pathParts = explode('/', $cleanPath);
				array_pop($pathParts);
				$parentPath = implode('/', $pathParts);
			}

			// Query for subfolders in the current path
			$qb = $this->db->getQueryBuilder();
			$searchPath = empty($cleanPath) ? 'files' : 'files/' . $cleanPath;
			
			$foldersQuery = $qb->select('f.name', 'f.path')
				->from('filecache', 'f')
				->leftJoin('f', 'storages', 's', 'f.storage = s.numeric_id')
				->where($qb->expr()->like('s.id', $qb->createNamedParameter('home::' . $userId . '%')))
				->andWhere($qb->expr()->eq('f.mimetype', $qb->createNamedParameter(2))) // Directory mimetype
				->andWhere($qb->expr()->like('f.path', $qb->createNamedParameter($searchPath . '/%')))
				->andWhere($qb->expr()->neq('f.name', $qb->createNamedParameter('.')))
				->andWhere($qb->expr()->neq('f.name', $qb->createNamedParameter('..')))
				->andWhere($qb->expr()->notLike('f.name', $qb->createNamedParameter('.%'))) // Skip hidden folders
				->orderBy('f.name', 'ASC')
				->setMaxResults(50);

			$foldersResult = $foldersQuery->executeQuery();
			
			while ($folder = $foldersResult->fetch()) {
				// Extract just the folder name (not full path)
				$folderName = $folder['name'];
				$folderPath = empty($cleanPath) ? $folderName : $cleanPath . '/' . $folderName;
				
				// Skip if this is not a direct child
				$relativePath = preg_replace('#^' . preg_quote($searchPath . '/') . '#', '', $folder['path']);
				if (strpos($relativePath, '/') !== false) {
					continue; // This is a deeper subfolder
				}
				
				$folders[] = [
					'path' => $folderPath,
					'name' => $folderName,
					'type' => 'folder'
				];
			}
			$foldersResult->closeCursor();

			return new DataResponse([
				'folders' => $folders,
				'currentPath' => $cleanPath,
				'parentPath' => $parentPath
			]);
			
		} catch (\Exception $e) {
			error_log('Dashy browseFolders error: ' . $e->getMessage());
			return new DataResponse([
				'folders' => [],
				'currentPath' => $path,
				'parentPath' => null,
				'error' => 'Failed to browse folders'
			]);
		}
	}
}
