<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class VerifiedRequest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verified:request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disminuye la cantidad de request cada 1 hora';

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
        DB::table('cuota_request')
            ->update(['actual'=>0]);
    }
}
