<?php

namespace App\Console\Commands;

use App\Models\PostView;
use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class ConsumeViewEvents extends Command
{
    protected $signature = 'rabbitmq:consume-views';

    protected $description = 'Consume post view events from RabbitMQ queue';

    private ?AMQPStreamConnection $connection = null;

    public function handle(): int
    {
        $this->info('Starting post view events consumer...');

        $connection = $this->getConnection();
        $channel = $connection->channel();

        $exchange = config('rabbitmq.exchanges.analytics');
        $queue = config('rabbitmq.queues.post_views');

        $channel->exchange_declare($exchange, 'topic', false, true, false);
        $channel->queue_declare($queue, false, true, false, false);
        $channel->queue_bind($queue, $exchange, 'post.viewed');

        $this->info("Waiting for messages on queue: {$queue}");

        $channel->basic_consume(
            $queue,
            '',
            false,
            false,
            false,
            false,
            function ($message) {
                $this->processMessage($message);
            }
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        return Command::SUCCESS;
    }

    private function processMessage($message): void
    {
        try {
            $data = json_decode($message->body, true);

            if (!$data) {
                $this->error('Invalid JSON message');
                $message->nack();
                return;
            }

            if (empty($data['post_uuid'])) {
                $this->error('Missing post_uuid');
                $message->nack();
                return;
            }

            PostView::create([
                'post_uuid' => $data['post_uuid'],
                'user_id' => $data['user_id'] ?? null,
                'ip_address' => $data['ip_address'] ?? '0.0.0.0',
                'user_agent' => $data['user_agent'] ?? null,
                'referer' => $data['referer'] ?? null,
                'viewed_at' => $data['viewed_at'] ?? now(),
            ]);

            $message->ack();
            $this->info("Recorded view for post: {$data['post_uuid']}");
        } catch (\Exception $e) {
            $this->error("Error processing message: {$e->getMessage()}");
            $message->nack();
        }
    }

    private function getConnection(): AMQPStreamConnection
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            $this->connection = new AMQPStreamConnection(
                config('rabbitmq.host'),
                config('rabbitmq.port'),
                config('rabbitmq.user'),
                config('rabbitmq.password'),
                config('rabbitmq.vhost'),
            );
        }

        return $this->connection;
    }

    public function __destruct()
    {
        if ($this->connection !== null && $this->connection->isConnected()) {
            $this->connection->close();
        }
    }
}
