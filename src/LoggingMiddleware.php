<?php
declare(strict_types=1);

namespace Elephox\Builder\RequestLogging;

use Closure;
use Elephox\Configuration\Contract\Environment;
use Elephox\Http\Contract\Request;
use Elephox\Http\Contract\ResponseBuilder;
use Elephox\Http\Contract\ServerRequest;
use Elephox\Http\ParameterSource;
use Elephox\Logging\Contract\LogLevel as LogLevelContract;
use Elephox\Logging\Contract\SinkLogger;
use Elephox\Logging\LogLevel;
use Elephox\Logging\SinkCapability;
use Elephox\Web\Contract\WebMiddleware;
use Psr\Log\LoggerInterface;

readonly class LoggingMiddleware implements WebMiddleware
{
	private RequestLoggingConfiguration $config;

	private bool $enableMessageTemplates;

	public function __construct(
		private LoggerInterface $logger,
		private Environment $environment,
		?RequestLoggingConfiguration $config,
	) {
		$this->config = $config ?? new RequestLoggingConfiguration();

		if ($this->logger instanceof SinkLogger) {
			$this->enableMessageTemplates = $this->logger->hasCapability(SinkCapability::MessageTemplates);
		} else {
			$this->enableMessageTemplates = false;
		}
	}

	public function handle(Request $request, Closure $next): ResponseBuilder
	{
		$start = -hrtime(true);
		$response = $next($request);
		$end = $start + hrtime(true);

		if ($this->shouldLog($request, $response)) {
			$this->logger->log($this->getLevel($request, $response), $this->getMessage($request, $response), $this->getContext($request, $response, $end));
		}

		return $response;
	}

	protected function shouldLog(Request $request, ResponseBuilder $response): bool
	{
		return $this->config->minimumLogLevel->getLevel() <= $this->getLevel($request, $response)->getLevel();
	}

	protected function getLevel(Request $request, ResponseBuilder $response): LogLevelContract
	{
		return $response->getException() !== null ? LogLevel::ERROR : LogLevel::DEBUG;
	}

	protected function getMessage(Request $request, ResponseBuilder $response): string
	{
		if ($this->enableMessageTemplates) {
			return "[{responseCode}] {requestUri}";
		} else {
			$responseCode = $response->getResponseCode()?->value ?? -1;

			return sprintf('[%s] %s', $responseCode < 0 ? 'unknown' : (string)$responseCode, $request->getUrl());
		}
	}

	protected function getContext(Request $request, ResponseBuilder $response, float $requestTimeNs): array
	{
		$data = [
			'requestTimeMs' => $requestTimeNs / 1e6, // convert ns to ms
			'responseCode' => $response->getResponseCode()?->value,
			'requestUri' => (string)$request->getUrl(),
			'request' => [
				'method' => $request->getMethod(),
				'url' => $request->getUrl()->toArray(),
				'headers' => $request->getHeaderMap()->select(fn (array|string $v) => is_array($v) && count($v) === 1 ? $v[0] : $v)->toArray(),
				'protocol_version' => $request->getProtocolVersion(),
			],
			'response' => [
				'code' => $response->getResponseCode(),
				'content_type' => $response->getContentType(),
				'headers' => $response->getHeaderMap()?->select(fn (array|string $v) => is_array($v) && count($v) === 1 ? $v[0] : $v)->toArray(),
				'protocol_version' => $response->getProtocolVersion(),
			],
		];

		if ($this->config->includeEnvironment) {
			$data['environment'] = $this->environment->asEnumerable()->toArray();
		}

		if ($this->config->includeBody) {
			$data['request']['body'] = htmlspecialchars($request->getBody()->getContents());
			$data['response']['body'] = htmlspecialchars($response->getBody()?->getContents() ?? '');
		}

		if ($request instanceof ServerRequest) {
			// TODO: improve context for ServerRequest instances

			$data['request']['cookies'] = $request->getCookieMap()->toArray();
			$data['request']['server'] = $request->getParameterMap()->allFrom(ParameterSource::Server)->toArray();
			$data['request']['post'] = $request->getParameterMap()->allFrom(ParameterSource::Post)->toArray();
			$data['request']['session'] = $request->getSessionMap()?->toArray();

			if ($this->config->includeFiles) {
				$data['request']['files'] = $request->getUploadedFileMap()->toArray();
			}
		}

		if ($exception = $response->getException()) {
			$data['exception'] = $exception;
		}

		return $data;
	}
}
