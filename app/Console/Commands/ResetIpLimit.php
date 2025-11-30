<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\IpAccess;
use App\Models\IpAccessLog;
use App\ApiAccessManager;

class ResetIpLimit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ip:reset-limit {ip_address} {--date= : Specific date to reset (Y-m-d format, defaults to today)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resets the daily limit for an IP address or domain';

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
        $ip_address = trim($this->argument('ip_address'));
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

        // Check if it's a domain or IP
        $domain = null;
        if (!filter_var($ip_address, FILTER_VALIDATE_IP) && !filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // Try to parse as domain
            $domain = ApiAccessManager::parseDomain($ip_address);
            if (!$domain) {
                $this->error("Invalid IP address or domain: {$ip_address}");
                return 1;
            }
        }

        // Find the IP access record
        if ($domain) {
            $ip_access = IpAccess::where('domain', $domain)->first();
        } else {
            $ip_access = IpAccess::where('ip_address', $ip_address)
                ->whereNull('domain')
                ->first();
        }

        if (!$ip_access) {
            $identifier = $domain ?: $ip_address;
            $this->error("IP address or domain '{$identifier}' not found in the system.");
            return 1;
        }

        // Find or create the access log for the specified date
        $log = IpAccessLog::where('ip_id', $ip_access->id)
            ->where('date', $date)
            ->first();

        if (!$log) {
            $identifier = $ip_access->domain ?: $ip_access->ip_address;
            $this->info("No access log found for '{$identifier}' on {$date}. Nothing to reset.");
            return 0;
        }

        // Store original values for display
        $original_count = $log->count;
        $original_limit_reached = $log->limit_reached;

        // Reset the log
        $log->count = 0;
        $log->limit_reached = 0;
        $log->save();

        $identifier = $ip_access->domain ?: $ip_access->ip_address;
        $this->info("Successfully reset daily limit for '{$identifier}' on {$date}.");
        $this->line("  Previous hits: {$original_count}");
        $this->line("  Limit reached: " . ($original_limit_reached ? 'Yes' : 'No'));
        $this->line("  New hits: 0");
        $this->line("  Limit reached: No");

        return 0;
    }
}

