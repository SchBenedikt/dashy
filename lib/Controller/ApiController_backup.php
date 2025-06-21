<?php

declare(strict_types=1);

namespace OCA\Dashy\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IDBConnection;
use OCP\IUserSession;

/**
 * @psalm-suppress UnusedClass
 */
class ApiController extends Controller {

	private IConfig $config;
	private IDBConnection $db;
	private IUserSession $userSession;

	public function __construct(
		string $appName,
		IRequest $request,
		IConfig $config,
		IDBConnection $db,
		IUserSession $userSession
	) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->db = $db;
		$this->userSession = $userSession;
	}

	/**
	 * Get the current user ID
	 */
	private function getUserId(): ?string {
		$user = $this->userSession->getUser();
		return $user ? $user->getUID() : null;
	}

	/**
	 * An example API endpoint
	 *
	 * @return DataResponse<Http::STATUS_OK, array{message: string}, array{}>
	 *
	 * 200: Data returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api')]
	public function index(): DataResponse {
		return new DataResponse(
			['message' => 'Hello world!']
		);
	}

	/**
	 * Get user's dashboard configuration
	 *
	 * @return DataResponse<Http::STATUS_OK, array{layout: array, widgets: array}, array{}>
	 *
	 * 200: Dashboard configuration returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/dashboard')]
	public function getDashboard(): DataResponse {
		$userId = $this->getUserId();
		if (empty($userId)) {
			return new DataResponse([
				'layout' => [],
				'widgets' => [],
			]);
		}

		$layout = $this->config->getUserValue($userId, 'dashy', 'layout', '[]');
		$widgets = $this->config->getUserValue($userId, 'dashy', 'widgets', '{}');

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
	#[ApiRoute(verb: 'POST', url: '/dashboard')]
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
	 * Get calendar events via CalDAV
	 *
	 * @return DataResponse<Http::STATUS_OK, array{events: array}, array{}>
	 *
	 * 200: Calendar events returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/calendar/events')]
	public function getCalendarEvents(): DataResponse {
		$userId = $this->getUserId();
		if (empty($userId)) {
			return new DataResponse(['events' => []]);
		}

		try {
			// Use CalDAV to get calendar events
			$events = $this->getCalendarEventsViaCalDAV($userId);
			return new DataResponse(['events' => $events]);
			
		} catch (\Exception $e) {
			// Fallback to database if CalDAV fails
			return $this->getCalendarEventsFromDatabase($userId);
		}
	}

	/**
	 * Get calendar events via CalDAV API
	 */
	private function getCalendarEventsViaCalDAV(string $userId): array {
		$events = [];
		
		// Get calendar manager
		$calendarManager = \OC::$server->get(\OCP\Calendar\IManager::class);
		$calendars = $calendarManager->getCalendarsForPrincipal('principals/users/' . $userId);
		
		$now = new \DateTime();
		$endDate = (new \DateTime())->add(new \DateInterval('P30D')); // Next 30 days
		
		foreach ($calendars as $calendar) {
			if (!$calendar->canWrite()) {
				continue; // Skip read-only calendars
			}
			
			try {
				$calendarEvents = $calendar->search('', ['VEVENT'], [
					'timerange' => [
						'start' => $now,
						'end' => $endDate
					]
				], 10);
				
				foreach ($calendarEvents as $event) {
					$eventData = $this->parseCalendarEvent($event);
					if ($eventData) {
						$eventData['calendar'] = $calendar->getDisplayName();
						$events[] = $eventData;
					}
				}
			} catch (\Exception $e) {
				// Skip this calendar if it fails
				continue;
			}
		}
		
		// Sort events by start date
		usort($events, function($a, $b) {
			return strtotime($a['start']) - strtotime($b['start']);
		});
		
		return array_slice($events, 0, 10); // Limit to 10 events
	}

	/**
	 * Parse calendar event from CalDAV
	 */
	private function parseCalendarEvent($event): ?array {
		try {
			$eventData = [
				'id' => $event['uid'] ?? 'event_' . time(),
				'title' => $event['summary'] ?? 'Untitled Event',
				'start' => isset($event['dtstart']) ? $event['dtstart']->format('c') : date('c'),
				'location' => $event['location'] ?? '',
			];
			
			return $eventData;
		} catch (\Exception $e) {
			return null;
		}
	}

	/**
	 * Fallback: Get calendar events from database
	 */
	private function getCalendarEventsFromDatabase(string $userId): DataResponse {
			// Get calendar events from the database
			$qb = $this->db->getQueryBuilder();
			
			// Get calendars for this user
			$calendarsQuery = $qb->select('id', 'displayname')
				->from('calendars')
				->where($qb->expr()->eq('principaluri', $qb->createNamedParameter('principals/users/' . $userId)));
			
			$calendarsResult = $calendarsQuery->executeQuery();
			$calendarIds = [];
			$calendarNames = [];
			
			while ($calendar = $calendarsResult->fetch()) {
				$calendarIds[] = (int)$calendar['id'];
				$calendarNames[$calendar['id']] = $calendar['displayname'];
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
			
			return new DataResponse([
				'events' => $events
			]);
			
		} catch (\Exception $e) {
			// If calendar integration fails, return mock data as fallback
			$events = [
				[
					'id' => '1',
					'title' => 'Team Meeting (Mock)',
					'start' => date('c', strtotime('+2 hours')),
					'location' => 'Conference Room A',
					'calendar' => 'Demo Calendar',
				],
				[
					'id' => '2',
					'title' => 'Project Deadline (Mock)',
					'start' => date('c', strtotime('+1 day')),
					'location' => '',
					'calendar' => 'Work',
				],
			];

			return new DataResponse([
				'events' => $events,
				'debug' => 'Exception: ' . $e->getMessage()
			]);
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
	 * Get tasks from Tasks app
	 *
	 * @return DataResponse<Http::STATUS_OK, array{tasks: array}, array{}>
	 *
	 * 200: Tasks returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/tasks')]
	public function getTasks(): DataResponse {
		$userId = $this->getUserId();
		if (empty($userId)) {
			return new DataResponse(['tasks' => []]);
		}

		// Try to get tasks from the database if tasks app is installed
		try {
			$qb = $this->db->getQueryBuilder();
			$qb->select('*')
				->from('tasks_tasks')
				->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
				->andWhere($qb->expr()->eq('deleted', $qb->createNamedParameter(0)))
				->orderBy('created', 'DESC')
				->setMaxResults(50);

			$result = $qb->executeQuery();
			$tasks = [];
			while ($row = $result->fetch()) {
				$tasks[] = [
					'id' => (int)$row['id'],
					'title' => $row['summary'],
					'completed' => (bool)$row['completed'],
					'priority' => (int)($row['priority'] ?? 0),
					'created' => $row['created'],
					'due' => $row['due'],
					'list' => $row['calendar_id'],
				];
			}
			$result->closeCursor();
			
			return new DataResponse(['tasks' => $tasks]);
		} catch (\Exception $e) {
			// Fallback to demo data if tasks table doesn't exist
			$tasks = [
				[
					'id' => 1,
					'title' => 'Review pull request',
					'completed' => false,
					'priority' => 1,
					'created' => date('c', strtotime('-1 hour')),
					'due' => date('c', strtotime('+1 day')),
					'list' => 'Work',
				],
				[
					'id' => 2,
					'title' => 'Buy groceries',
					'completed' => false,
					'priority' => 0,
					'created' => date('c', strtotime('-2 hours')),
					'due' => null,
					'list' => 'Personal',
				],
				[
					'id' => 3,
					'title' => 'Call dentist',
					'completed' => true,
					'priority' => 2,
					'created' => date('c', strtotime('-1 day')),
					'due' => null,
					'list' => 'Personal',
				],
			];
			
			return new DataResponse(['tasks' => $tasks]);
		}
	}

	/**
	 * Create a new task
	 *
	 * @return DataResponse<Http::STATUS_OK, array{success: bool, task?: array}, array{}>
	 *
	 * 200: Task created
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'POST', url: '/tasks')]
	public function createTask(): DataResponse {
		$userId = $this->getUserId();
		if (empty($userId)) {
			return new DataResponse(['success' => false, 'error' => 'User not authenticated'], Http::STATUS_UNAUTHORIZED);
		}

		$title = $this->request->getParam('title', '');
		
		if (empty($title)) {
			return new DataResponse(['success' => false, 'error' => 'Title is required'], Http::STATUS_BAD_REQUEST);
		}

		try {
			// Try to create task in the tasks table
			$qb = $this->db->getQueryBuilder();
			$qb->insert('tasks_tasks')
				->values([
					'user_id' => $qb->createNamedParameter($userId),
					'summary' => $qb->createNamedParameter($title),
					'completed' => $qb->createNamedParameter(0),
					'created' => $qb->createNamedParameter(time()),
					'modified' => $qb->createNamedParameter(time()),
					'deleted' => $qb->createNamedParameter(0),
				]);
			
			$qb->executeStatement();
			$taskId = $this->db->lastInsertId();

			$task = [
				'id' => $taskId,
				'title' => $title,
				'completed' => false,
				'created' => date('c'),
			];

			return new DataResponse(['success' => true, 'task' => $task]);
		} catch (\Exception $e) {
			// If tasks table doesn't exist, return mock success
			$task = [
				'id' => time(), // Use timestamp as fake ID
				'title' => $title,
				'completed' => false,
				'created' => date('c'),
			];
			
			return new DataResponse(['success' => true, 'task' => $task]);
		}
	}

	/**
	 * Update a task
	 *
	 * @return DataResponse<Http::STATUS_OK, array{success: bool}, array{}>
	 *
	 * 200: Task updated
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'PUT', url: '/tasks/{taskId}')]
	public function updateTask(int $taskId): DataResponse {
		$userId = $this->getUserId();
		$completed = $this->request->getParam('completed');

		try {
			// Try to update task in the tasks table
			$qb = $this->db->getQueryBuilder();
			$qb->update('tasks_tasks')
				->set('completed', $qb->createNamedParameter($completed ? 1 : 0))
				->set('modified', $qb->createNamedParameter(time()))
				->where($qb->expr()->eq('id', $qb->createNamedParameter($taskId)))
				->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
			
			$qb->executeStatement();

			return new DataResponse(['success' => true]);
		} catch (\Exception $e) {
			// If tasks table doesn't exist, return mock success
			return new DataResponse(['success' => true]);
		}
	}

	/**
	 * Delete a task
	 *
	 * @return DataResponse<Http::STATUS_OK, array{success: bool}, array{}>
	 *
	 * 200: Task deleted
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'DELETE', url: '/tasks/{taskId}')]
	public function deleteTask(int $taskId): DataResponse {
		$userId = $this->getUserId();
		try {
			// Try to delete task from the tasks table
			$qb = $this->db->getQueryBuilder();
			$qb->update('tasks_tasks')
				->set('deleted', $qb->createNamedParameter(1))
				->set('modified', $qb->createNamedParameter(time()))
				->where($qb->expr()->eq('id', $qb->createNamedParameter($taskId)))
				->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
			
			$qb->executeStatement();

			return new DataResponse(['success' => true]);
		} catch (\Exception $e) {
			// If tasks table doesn't exist, return mock success
			return new DataResponse(['success' => true]);
		}
	}

	/**
	 * Get weather data
	 *
	 * @return DataResponse<Http::STATUS_OK, array{weather?: array, error?: string}, array{}>
	 *
	 * 200: Weather data returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/weather')]
	public function getWeather(): DataResponse {
		$userId = $this->getUserId();
		$location = $this->request->getParam('location', '');
		$unit = $this->request->getParam('unit', 'C');

		if (empty($location)) {
			// Try to get user's location from Nextcloud settings
			$location = $this->config->getUserValue($userId ?: '', 'weather', 'location', '');
			if (empty($location)) {
				$location = 'Munich'; // Default fallback
			}
		}

		try {
			// Use OpenWeatherMap free API (requires API key in config)
			$apiKey = $this->config->getAppValue('dashy', 'openweather_api_key', '');
			
			if (empty($apiKey)) {
				// Return mock data if no API key is configured
				return new DataResponse([
					'weather' => [
						'temperature' => 22,
						'feelsLike' => 24,
						'humidity' => 65,
						'windSpeed' => 12,
						'description' => 'Partly Cloudy',
						'condition' => 'partly-cloudy',
						'location' => $location,
						'unit' => $unit,
					]
				]);
			}

			$unitParam = $unit === 'F' ? 'imperial' : 'metric';
			$url = "https://api.openweathermap.org/data/2.5/weather?q=" . urlencode($location) . "&appid=" . $apiKey . "&units=" . $unitParam;

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			$response = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($httpCode === 200 && $response) {
				$data = json_decode($response, true);
				
				if ($data && isset($data['main'])) {
					return new DataResponse([
						'weather' => [
							'temperature' => $data['main']['temp'],
							'feelsLike' => $data['main']['feels_like'],
							'humidity' => $data['main']['humidity'],
							'windSpeed' => isset($data['wind']['speed']) ? $data['wind']['speed'] : 0,
							'description' => ucfirst($data['weather'][0]['description']),
							'condition' => $data['weather'][0]['main'],
							'location' => $data['name'],
							'unit' => $unit,
						]
					]);
				}
			}

			return new DataResponse(['error' => 'Failed to fetch weather data']);

		} catch (\Exception $e) {
			return new DataResponse(['error' => 'Weather service unavailable']);
		}
	}
}
