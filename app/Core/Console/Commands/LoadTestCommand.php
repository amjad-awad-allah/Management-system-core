<?php

namespace App\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use App\Core\Models\User;

class LoadTestCommand extends Command
{
    protected $signature = 'core:load-test {--requests=200}';
    protected $description = 'Run a local load test against the Audit Logs API using internal dispatch to test DB and framework limits';

    public function handle()
    {
        $totalRequests = (int) $this->option('requests');
        $this->info("Starting Load Test: {$totalRequests} sequential internal requests to Audit Logs API");

        $user = User::whereHas('roles', function($q) { $q->where('name', 'Super Admin'); })->first();
        if (!$user) {
            $user = clone User::first(); 
        }

        $kernel = app()->make(\Illuminate\Contracts\Http\Kernel::class);

        $startTime = microtime(true);
        $successCount = 0;
        $failCount = 0;

        for ($i = 0; $i < $totalRequests; $i++) {
            $request = \Illuminate\Http\Request::create('/api/v1/audit-logs', 'GET');
            $request->headers->set('Accept', 'application/json');
            
            // Bypass Sanctum by setting the user on the guard directly
            auth('sanctum')->setUser($user);

            try {
                $response = $kernel->handle($request);
                if ($response->getStatusCode() === 200) {
                    $successCount++;
                } else {
                    $failCount++;
                }
                $kernel->terminate($request, $response);
            } catch (\Exception $e) {
                Log::error("Load Test Error: " . $e->getMessage());
                $failCount++;
            }

            if ($i % 20 === 0) {
                $this->output->write('.');
            }
        }

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        $requestsPerSecond = $duration > 0 ? round($totalRequests / $duration, 2) : $totalRequests;

        $this->newLine(2);
        $this->info("Load Test Completed in {$duration} seconds ({$requestsPerSecond} req/s)");
        $this->info("Successful Requests: {$successCount}");
        
        if ($failCount > 0) {
            $this->error("Failed Requests: {$failCount}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
