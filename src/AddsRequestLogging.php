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
		if ($this->getServices()->has(LoggingMiddleware::class)) {
			$middleware = $this->getServices()->requireService(LoggingMiddleware::class);
		} else {
			$middleware = $this->getServices()->resolver()->instantiate(LoggingMiddleware::class);
		}

		$this->getPipeline()->push($middleware);
	}
}
