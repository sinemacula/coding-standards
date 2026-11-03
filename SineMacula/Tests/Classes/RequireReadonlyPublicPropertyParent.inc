<?php

namespace App\Jobs;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SendReport extends Job
{
    public int $tries = 3;

    public int $timeout = 120;

    public string $status = 'new';

    public function __construct(
        public int $backoff,
        public string $recipient,
    ) {
    }
}

class SendInvoice extends \App\Jobs\Job
{
    public int $tries = 5;
}

class ArchiveLogs extends job
{
    public int $timeout = 60;
}

class BroadcastReport extends Job implements ShouldBroadcast
{
    public int $tries = 1;

    public string $connection = 'pusher';

    public string $channel = 'reports';
}

class RetrySendReport extends SendReport
{
    public int $tries = 1;
}

class ExportReport extends JobBase
{
    public int $tries = 1;
}

$job = new class extends Job {
    public int $tries = 1;

    public int $attempts = 0;
};
