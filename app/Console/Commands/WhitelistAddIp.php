<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\ConfigManager;
use App\ApiAccessManager;

class WhitelistAddIp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whitelist:add-ip {ip_address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds an IP address to the daily access whitelist';

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
        
        // Validate IP address format
        if (!filter_var($ip_address, FILTER_VALIDATE_IP) && !filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // If not a valid IP, check if it's a domain
            $domain = ApiAccessManager::parseDomain($ip_address);
            if (!$domain) {
                $this->error("Invalid IP address or domain: {$ip_address}");
                return 1;
            }
            $ip_address = $domain; // Use parsed domain
        }

        // Get current whitelist
        $current_whitelist = config('bss.daily_access_whitelist') ?: '';
        
        // Parse existing items
        $items = [];
        if (!empty($current_whitelist)) {
            $items = array_filter(
                array_map('trim', 
                    explode("\n", str_replace(["\r\n", "\r"], "\n", $current_whitelist))
                )
            );
        }

        // Normalize the IP/domain for comparison (using parseDomain for consistency)
        $normalized = ApiAccessManager::parseDomain($ip_address) ?: $ip_address;
        
        // Check if already in whitelist
        foreach ($items as $item) {
            $normalized_item = ApiAccessManager::parseDomain($item) ?: $item;
            if ($normalized_item === $normalized) {
                $this->info("IP address or domain '{$ip_address}' is already in the whitelist.");
                return 0;
            }
        }

        // Add to whitelist
        $items[] = $ip_address;
        $new_whitelist = implode("\n", $items);

        // Save the updated whitelist
        ConfigManager::setConfig('bss.daily_access_whitelist', $new_whitelist, 0);

        $this->info("Successfully added '{$ip_address}' to the daily access whitelist.");
        
        return 0;
    }
}

