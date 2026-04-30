<?php

namespace App\Console\Commands;

use App\Models\UserActivityLog;
use Illuminate\Console\Command;

class PruneActivityLogs extends Command
{
    protected $signature = 'activity-logs:prune {--days=90 : Dias de retencion de logs}';

    protected $description = 'Elimina registros de auditoria antiguos segun la retencion configurada.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $threshold = now()->subDays($days);

        $deleted = UserActivityLog::query()
            ->where('created_at', '<', $threshold)
            ->delete();

        $this->info("Logs de auditoria eliminados: {$deleted}");
        $this->line('Fecha limite: ' . $threshold->format('Y-m-d H:i:s'));

        return self::SUCCESS;
    }
}
