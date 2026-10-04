<?php

namespace App\Logging;

use Monolog\Handler\AbstractHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\LogRecord;

class FeatureLogHandler extends AbstractHandler
{
    private array $handlers = [];

    public function __construct(private array $config)
    {
        parent::__construct($config['level'] ?? 'debug');
    }

    public function handle(LogRecord $record): bool
    {
        if (! $this->isHandling($record)) {
            return false;
        }

        $request = FeatureLog::request();
        $feature = $this->config['feature'] ?? $record->context['feature'] ?? FeatureLog::resolve($request);
        if (! in_array($feature, FeatureLog::features(), true)) {
            $feature = 'aplikasi';
        }
        $handler = $this->handlers[$feature] ??= new RotatingFileHandler(
            ($this->config['directory'] ?? storage_path('logs')).'/'.$feature.'.log',
            max(1, (int) ($this->config['days'] ?? 14)),
            $this->getLevel(),
            true,
            null,
            true,
            timezone: new \DateTimeZone(config('app.timezone', 'Asia/Jakarta'))
        );
        $context = ['feature' => $feature];
        if ($request) {
            $context += [
                'request_id' => $request->attributes->get('log_request_id'),
                'method' => $request->method(),
                'route' => $request->route()?->uri(),
            ];
        }
        $handler->handle($record->with(context: array_replace($record->context, $context)));

        return ! $this->bubble;
    }

    public function close(): void
    {
        foreach ($this->handlers as $handler) {
            $handler->close();
        }
        $this->handlers = [];
    }

    public function reset(): void
    {
        $this->close();
    }
}
