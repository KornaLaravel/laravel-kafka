<?php declare(strict_types=1);

namespace Junges\Kafka;

use Illuminate\Support\Traits\Macroable;
use Junges\Kafka\Consumers\Builder as ConsumerBuilder;
use Junges\Kafka\Contracts\KafkaManager;
use Junges\Kafka\Contracts\MessageProducer;
use Junges\Kafka\Producers\Builder as ProducerBuilder;

class Factory implements KafkaManager
{
    use Macroable;

    /** Creates a new ProducerBuilder instance, setting brokers and topic. */
    public function publish(string $broker = null): MessageProducer
    {
        return new ProducerBuilder(
            broker: $broker ?? config('kafka.brokers')
        );
    }

    /** Return a ConsumerBuilder instance.  */
    public function createConsumer(array $topics = [], string $groupId = null, string $brokers = null): ConsumerBuilder
    {
        return ConsumerBuilder::create(
            brokers: $brokers ?? config('kafka.brokers'),
            topics: $topics,
            groupId: $groupId ?? config('kafka.consumer_group_id')
        );
    }
}