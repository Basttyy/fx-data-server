<?php
require_once './vendor/autoload.php';
require_once 'src/libs/helpers.php';

use Basttyy\FxDataServer\Console\QueueInterface;
use Basttyy\FxDataServer\Console\Job_Queue;

$job_Queue = new Job_Queue('mysql', [
    'mysql' => [
        'table_name' => 'jobs',     //the table that jobs will be stored in
        'use_compression' => true
    ]
]);

$pdo = new PDO('mysql:dbname=tcpmtbridge;host=127.0.0.1', 'root', '123456789');
$job_Queue->addQueueConnection($pdo);
$job_Queue->watchPipeline('default');

///TODO: Job_Queue class db based methods should use 
$job = await(\React\Promise\resolve($job_Queue->getNextJobAndReserve()));

if(empty($job)) {
    $sleeping1 = true;
    $delay = await(sleep(1, $loop));
    $sleeping1 = false;
    return;
}
$jobs_count++;

echo "Processing job {$job['id']}\n";
$payload = $job['payload'];

try {
    $job_obj = unserialize($payload);

    if ($job_obj instanceof QueueInterface) {
        $job_obj->setJob($job);
        $job_obj->setQueue($job_Queue);
        $resp = await ($job_obj->handle());
    }
} catch(Exception $e) {
    $job_Queue->buryJob($job, $job_obj->getDelay());
}

$sleeping1 = false;
$sleeping2 = false;
$jobs_count = 0;
$bury_count = 0;
$max_jobs = 10;
$bury_max_jobs = 10;

$loop->addPeriodicTimer(0.01, async(function (TimerInterface $timer) use ($loop, $job_Queue, &$jobs_count, $max_jobs, &$sleeping1) {
    if ($sleeping1)
        return;

    if ($jobs_count >= $max_jobs)
        return;
    
    ///TODO: Job_Queue class db based methods should use 
    $job = await(\React\Promise\resolve($job_Queue->getNextJobAndReserve()));
    
    if(empty($job)) {
        $sleeping1 = true;
        $delay = await(sleep(1, $loop));
        $sleeping1 = false;
        return;
    }
    $jobs_count++;

    echo "Processing job {$job['id']}\n";
    $payload = $job['payload'];

    try {
        $job_obj = unserialize($payload);

        if ($job_obj instanceof QueueInterface) {
            $job_obj->setJob($job);
            $job_obj->setQueue($job_Queue);
            $resp = await ($job_obj->handle());
        }
    } catch(Exception $e) {
        $job_Queue->buryJob($job, $job_obj->getDelay());
    }
    $jobs_count--;
}));

$loop->addPeriodicTimer(0.01, async(function ($timer) use ($loop, $bury_max_jobs, $job_Queue, &$bury_count, &$sleeping2) {
    if ($sleeping2)
        return;
    
    if ($bury_count >= $bury_max_jobs) {
        return;
    }
    $job = await(\React\Promise\resolve($job_Queue->getNextBuriedJob()));

    if (empty($job)) {
        $sleeping2 = true;
        $delay = await(sleep(1, $loop));
        $sleeping2 = false;
        return;
    }
    $bury_count++;

    echo "Processing job {$job['id']}\n";
    $payload = $job['payload'];
    $delay = await(sleep(4, $loop));

    try {
        $job_obj = unserialize($payload);

        if ($job_obj instanceof QueueInterface) {
            $job_obj->setJob($job);
            $job_obj->setQueue($job_Queue);
            $resp = await($job_obj->handle());
        }
        $bury_count--;
    } catch (Exception $e) {
        $bury_count--;
        $job_Queue->buryJob($job, $job_obj->getDelay());
    }
}));

$loop->run();