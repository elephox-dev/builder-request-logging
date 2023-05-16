<?php
declare(strict_types=1);

namespace Elephox\Builder\RequestLogging;

use Elephox\DI\Contract\ServiceCollection;
use Elephox\Web\RequestPipelineBuilder;

trait AddsRequestLogging
{
	abstract protected function getServices(): ServiceCollection;

	abstract protected function getPipeline(): RequestPipelineBuilder;

	public function addRequestLogging(?RequestLoggingConfiguration $config = null): void
	{
		if ($config === null)
			$this->getServices()->addSingleton(RequestLoggingConfiguration::class, factory: RequestLoggingConfiguration::fromAppConfiguration(...));
		else
			$this->getServices()->addSingleton(RequestLoggingConfiguration::class, instance: $config);

		$this->getServices()->addSingleton(LoggingMiddleware::class);
		$this->getPipeline()->push(LoggingMiddleware::class);
	}
}
