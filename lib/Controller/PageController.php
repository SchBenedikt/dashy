<?php

declare(strict_types=1);

namespace OCA\Dashy\Controller;

use OCA\Dashy\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\TemplateResponse;

/**
 * @psalm-suppress UnusedClass
 */
class PageController extends Controller {
	#[NoCSRFRequired]
	#[NoAdminRequired]
	#[OpenAPI(OpenAPI::SCOPE_IGNORE)]
	public function index(): TemplateResponse {
		return new TemplateResponse(
			Application::APP_ID,
			'index',
		);
	}
}
