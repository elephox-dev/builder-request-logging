<?php
declare(strict_types=1);

namespace Elephox\Builder\RequestLogging;

use Elephox\Configuration\Contract\Configuration;
use Elephox\Logging\Contract\LogLevel as LogLevelContract;
use Elephox\Logging\LogLevel;
use InvalidArgumentException;

final readonly class RequestLoggingConfiguration
{
	public static function fromAppConfiguration(Configuration $configuration): self
	{
		return new self(
			self::stringToLevel($configuration['request-logging:minimum-log-level']),
			$configuration['request-logging:include-body'] ?? false,
			$configuration['request-logging:include-files'] ?? false,
			$configuration['request-logging:include-environment'] ?? false,
		);
	}

	private static function stringToLevel(?string $level): LogLevelContract
	{
		if ($level === null) return LogLevel::DEBUG;

		return match (strtolower($level)) {
			'verbose', 'trace', 'debug' => LogLevel::DEBUG,
			'info', 'information' => LogLevel::INFO,
			'notice' => LogLevel::NOTICE,
			'warning' => LogLevel::WARNING,
			'error' => LogLevel::ERROR,
			'critical', 'fatal' => LogLevel::CRITICAL,
			'alert' => LogLevel::ALERT,
			'emergency' => LogLevel::EMERGENCY,
			default => throw new InvalidArgumentException("Unknown log level: $level"),
		};
	}

	public function __construct(
		public LogLevelContract $minimumLogLevel = LogLevel::DEBUG,
		public bool $includeBody = false,
		public bool $includeFiles = false,
		public bool $includeEnvironment = false,
	) {
	}
}
