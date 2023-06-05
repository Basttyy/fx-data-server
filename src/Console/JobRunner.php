<?php
require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'src/libs/helpers.php';

use Basttyy\FxDataServer\Console\QueueInterface;
use Basttyy\FxDataServer\Console\Job_Queue;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$dotenv->required(['DB_ADAPTER', 'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PORT', 'DB_CHARSET', 'CHRON_INTERVAL'])->notEmpty();

$dbtype = getenv('DB_ADAPTER');
$dbhost = getenv("DB_HOST");
$dbname = getenv('DB_NAME');
$dbuser = getenv('DB_USER');
$dbport = getenv('DB_PORT');
$dbcharset = getenv('DB_CHARSET');
$chron_interval = getenv('CHRON_INTERVAL');

$start_time = time();
$end_time = time() + env('CHRON_INTERVAL');

$job_Queue = new Job_Queue('mysql', [
    'mysql' => [
        'table_name' => 'jobs',     //the table that jobs will be stored in
        'use_compression' => true
    ]
]);

$pdo = new PDO("D:dbname=tcpmtbridge;host=127.0.0.1", 'root', '123456789');
$job_Queue->addQueueConnection($pdo);
$job_Queue->watchPipeline('default');

///TODO: Job_Queue class db based methods should use 
$job = $job_Queue->getNextJobAndReserve();

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
        $resp = $job_obj->handle();
    }
} catch(Exception $e) {
    $job_Queue->buryJob($job, $job_obj->getDelay());
}