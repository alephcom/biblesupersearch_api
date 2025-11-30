<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\IpAccess;
use App\Models\IpAccessLog;

class BlockedReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blocked:report {--date= : Specific date to check (Y-m-d format, defaults to today)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists IP addresses that have used their daily allowed requests';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $date_option = $this->option('date');
        
        // Parse date or use today
        if ($date_option) {
            $date = date('Y-m-d', strtotime($date_option));
            if ($date === false || $date === '1970-01-01') {
                $this->error("Invalid date format. Please use Y-m-d format (e.g., 2024-01-15)");
                return 1;
            }
        } else {
            $date = date('Y-m-d');
        }

        // Query IP access logs where limit_reached = 1 for the specified date
        $blocked_logs = IpAccessLog::where('date', $date)
            ->where('limit_reached', 1)
            ->with('ipAccess')
            ->get();

        if ($blocked_logs->isEmpty()) {
            $this->info("No IP addresses reached their daily limit on {$date}.");
            return 0;
        }

        // Prepare table data
        $table_data = [];
        foreach ($blocked_logs as $log) {
            $ip_access = $log->ipAccess;
            
            if (!$ip_access) {
                continue; // Skip if IP access record doesn't exist
            }

            $identifier = $ip_access->domain ?: $ip_access->ip_address;
            
            // Get the limit - prefer custom limit, then access level limit, then default config
            $limit = $ip_access->limit;
            if ($limit === null && $ip_access->accessLevel) {
                $limit = $ip_access->accessLevel->limit;
            }
            if ($limit === null) {
                $limit = config('bss.daily_access_limit');
            }
            
            $table_data[] = [
                'IP/Domain' => $identifier,
                'IP Address' => $ip_access->ip_address ?: 'N/A',
                'Domain' => $ip_access->domain ?: 'N/A',
                'Hits' => $log->count,
                'Limit' => $limit > 0 ? $limit : 'Unlimited',
                'Date' => $date,
            ];
        }

        // Display results
        $this->info("IP addresses that reached their daily limit on {$date}:");
        $this->newLine();
        $this->table(
            ['IP/Domain', 'IP Address', 'Domain', 'Hits', 'Limit', 'Date'],
            $table_data
        );

        $this->newLine();
        $this->info("Total: " . count($table_data) . " IP address(es)");

        return 0;
    }
}

