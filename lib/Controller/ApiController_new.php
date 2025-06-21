<?php

declare(strict_types=1);

namespace OCA\Dashy\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IConfig;
use OCP\IUserSession;

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
	#[ApiRoute(verb: 'GET', url: '/dashboard')]
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
	 * Get calendar events
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

		// Return mock tasks for now
		$tasks = [
			[
				'id' => '1',
				'summary' => 'Sample Task',
				'completed' => false,
				'priority' => 1,
				'list' => 'Personal',
			],
		];

		return new DataResponse(['tasks' => $tasks]);
	}

	/**
	 * Get weather information
	 *
	 * @return DataResponse<Http::STATUS_OK, array{weather: array}, array{}>
	 *
	 * 200: Weather information returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/weather')]
	public function getWeather(): DataResponse {
		// Return mock weather data for now
		$weather = [
			'temperature' => 22,
			'condition' => 'Sunny',
			'location' => 'Sample City',
		];

		return new DataResponse(['weather' => $weather]);
	}
}
