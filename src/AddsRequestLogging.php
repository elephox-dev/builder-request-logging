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
		$middleware = $this->getServices()->get(LoggingMiddleware::class);
		if ($middleware === null) {
			$middleware = $this->getServices()->resolver()->instantiate(LoggingMiddleware::class);
		}

		$this->getPipeline()->push($middleware);
	}
}
