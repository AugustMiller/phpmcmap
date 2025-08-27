<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use xPaw\SourceQuery\SourceQuery;

class Rcon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:rcon {str}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a command to the RCON server';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $rcon = new SourceQuery;

        try {
            $rcon->Connect(env('MC_RCON_SERVER'), env('MC_RCON_PORT'));
            $rcon->SetRconPassword(env('MC_RCON_PASSWORD'));

            $this->info($rcon->Rcon($this->argument('str')));
        } catch(\Exception $e) {
            $this->error("Failed to connect to the RCON server: {$e->getMessage()}");
        } finally {
            $rcon->Disconnect();
        }
    }
}
