<?php

namespace App\Console\Commands;

use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkProjectsInProgress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:mark-in-progress';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark projects as in-progress if today is their start_date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();

        $projects = Project::whereDate('start_date', $today)
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'in-progress')
            ->get();

        foreach ($projects as $project) {
            $project->status = 'in-progress';
            $project->save();
        }

        $this->info("Marked {$projects->count()} project(s) as in-progress.");
    }
}
