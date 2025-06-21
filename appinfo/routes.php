<?php

declare(strict_types=1);

return [
	'routes' => [
		// Main page route
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		
		// Dashboard management
		['name' => 'api#getDashboard', 'url' => '/api/dashboard', 'verb' => 'GET'],
		['name' => 'api#saveDashboard', 'url' => '/api/dashboard', 'verb' => 'POST'],
		
		// Calendar integration
		['name' => 'api#getCalendarEvents', 'url' => '/api/calendar/events', 'verb' => 'GET'],
		
		// Tasks integration
		['name' => 'api#getTasks', 'url' => '/api/tasks', 'verb' => 'GET'],
		['name' => 'api#updateTask', 'url' => '/api/tasks/{taskId}', 'verb' => 'PUT'],
		
		// Weather integration
		['name' => 'api#getWeather', 'url' => '/api/weather', 'verb' => 'GET'],
		
		// Contacts integration
		['name' => 'api#getContacts', 'url' => '/api/contacts', 'verb' => 'GET'],
		
		// Files integration
		['name' => 'api#getFiles', 'url' => '/api/files', 'verb' => 'GET'],
		
		// Notes integration
		['name' => 'api#getNotes', 'url' => '/api/notes', 'verb' => 'GET'],
		['name' => 'api#createNote', 'url' => '/api/notes', 'verb' => 'POST'],
		['name' => 'api#updateNote', 'url' => '/api/notes/{noteId}', 'verb' => 'PUT'],
		['name' => 'api#getNotesFolders', 'url' => '/api/notes/folders', 'verb' => 'GET'],
		['name' => 'api#browseFolders', 'url' => '/api/folders/browse', 'verb' => 'GET'],
		
		// Bookmarks integration
		['name' => 'api#getBookmarks', 'url' => '/api/bookmarks', 'verb' => 'GET'],
		['name' => 'api#createBookmark', 'url' => '/api/bookmarks', 'verb' => 'POST'],
		['name' => 'api#trackBookmarkAccess', 'url' => '/api/bookmarks/{bookmarkId}/access', 'verb' => 'POST'],
	],
];
