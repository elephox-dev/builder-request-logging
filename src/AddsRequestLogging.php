<?php
declare(strict_types=1);

namespace Elephox\Builder\RequestLogging;

use Elephox\DI\Contract\ServiceCollection;
use Elephox\Web\RequestPipelineBuilder;

trait AddsRequestLogging
{
	abstract protected function getServices(): ServiceCollection;

	abstract protected function getPipeline(): RequestPipelineBuilder;

	public function addRequestLogging(): void
	{
		$this->getServices()->addSingleton(LoggingMiddleware::class);
		$this->getPipeline()->push(LoggingMiddleware::class);
	}
}
