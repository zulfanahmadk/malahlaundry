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
        $branch = $request?->attributes->get('branch');
        $suffix = $branch
            ? (\Illuminate\Support\Str::slug($branch->name) ?: 'cabang-'.$branch->id).'_'.(\Illuminate\Support\Str::slug($branch->store_name) ?: 'toko')
            : 'sistem_global';
        $filename = $feature.'_'.$suffix;
        $handler = $this->handlers[$filename] ??= new RotatingFileHandler(
            ($this->config['directory'] ?? storage_path('logs')).'/'.$filename.'.log',
            3,
            $this->getLevel(),
            true,
            null,
            true,
            timezone: new \DateTimeZone(config('app.timezone', 'Asia/Jakarta'))
        );
        $context = ['feature' => $feature, 'branch_id' => $branch?->id, 'branch_name' => $branch?->name, 'store_name' => $branch?->store_name];
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
