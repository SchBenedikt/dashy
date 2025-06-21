<?php

declare(strict_types=1);

namespace OCA\Dashy\AppInfo;

use OCA\Dashy\Controller\ApiController;
use OCA\Dashy\Controller\PageController;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IUserSession;

class Application extends App implements IBootstrap {
	public const APP_ID = 'dashy';

	/** @psalm-suppress PossiblyUnusedMethod */
	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		// Register controllers
		$context->registerService(ApiController::class, function ($c) {
			return new ApiController(
				self::APP_ID,
				$c->get(IRequest::class),
				$c->get(IConfig::class),
				$c->get(IDBConnection::class),
				$c->get(IUserSession::class),
				$c->get(\OCP\App\IAppManager::class)
			);
		});
		
		$context->registerService(PageController::class, function ($c) {
			return new PageController(
				self::APP_ID,
				$c->get(IRequest::class)
			);
		});
	}

	public function boot(IBootContext $context): void {
	}
}
