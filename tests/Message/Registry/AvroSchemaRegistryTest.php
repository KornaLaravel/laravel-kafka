<?php declare(strict_types=1);

namespace Junges\Kafka\Tests\Message\Registry;

use AvroSchema;
use FlixTech\SchemaRegistryApi\Registry;
use Junges\Kafka\Contracts\KafkaAvroSchemaRegistry;
use Junges\Kafka\Exceptions\SchemaRegistryException;
use Junges\Kafka\Message\Registry\AvroSchemaRegistry;
use Junges\Kafka\Tests\LaravelKafkaTestCase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;

final class AvroSchemaRegistryTest extends LaravelKafkaTestCase
{
    #[Test]
    public function add_body_schema_mapping_for_topic(): void
    {
        $flixRegistry = $this->getMockForAbstractClass(Registry::class);

        $schema = $this->getMockForAbstractClass(KafkaAvroSchemaRegistry::class);

        $registry = new AvroSchemaRegistry($flixRegistry);

        $registry->addBodySchemaMappingForTopic('test', $schema);

        $reflectionProperty = new ReflectionProperty($registry, 'schemaMapping');
        $reflectionProperty->setAccessible(true);

        $schemaMapping = $reflectionProperty->getValue($registry);

        $this->assertArrayHasKey(AvroSchemaRegistry::BODY_IDX, $schemaMapping);
        $this->assertArrayHasKey('test', $schemaMapping[AvroSchemaRegistry::BODY_IDX]);
        $this->assertSame($schema, $schemaMapping[AvroSchemaRegistry::BODY_IDX]['test']);
    }

    #[Test]
    public function add_key_schema_mapping_for_topic(): void
    {
        $flixRegistry = $this->getMockForAbstractClass(Registry::class);

        $schema = $this->getMockForAbstractClass(KafkaAvroSchemaRegistry::class);

        $registry = new AvroSchemaRegistry($flixRegistry);

        $registry->addKeySchemaMappingForTopic('test2', $schema);

        $reflectionProperty = new ReflectionProperty($registry, 'schemaMapping');
        $reflectionProperty->setAccessible(true);

        $schemaMapping = $reflectionProperty->getValue($registry);

        $this->assertArrayHasKey(AvroSchemaRegistry::KEY_IDX, $schemaMapping);
        $this->assertArrayHasKey('test2', $schemaMapping[AvroSchemaRegistry::KEY_IDX]);
        $this->assertSame($schema, $schemaMapping[AvroSchemaRegistry::KEY_IDX]['test2']);
    }

    #[Test]
    public function has_body_schema_mapping_for_topic(): void
    {
        $flixRegistry = $this->getMockForAbstractClass(Registry::class);
        $schema = $this->getMockForAbstractClass(KafkaAvroSchemaRegistry::class);

        $registry = new AvroSchemaRegistry($flixRegistry);
        $registry->addBodySchemaMappingForTopic('test', $schema);

        $this->assertTrue($registry->hasBodySchemaForTopic('test'));
        $this->assertFalse($registry->hasBodySchemaForTopic('test2'));
    }

    #[Test]
    public function has_key_schema_mapping_for_topic(): void
    {
        $flixRegistry = $this->getMockForAbstractClass(Registry::class);
        $schema = $this->getMockForAbstractClass(KafkaAvroSchemaRegistry::class);

        $registry = new AvroSchemaRegistry($flixRegistry);
        $registry->addKeySchemaMappingForTopic('test', $schema);

        $this->assertTrue($registry->hasKeySchemaForTopic('test'));
        $this->assertFalse($registry->hasKeySchemaForTopic('test2'));
    }

    #[Test]
    public function get_body_schema_for_topic_with_no_mapping(): void
    {
        $this->expectException(SchemaRegistryException::class);
        $this->expectExceptionMessage(
            sprintf(
                SchemaRegistryException::SCHEMA_MAPPING_NOT_FOUND,
                'test',
                AvroSchemaRegistry::BODY_IDX
            )
        );

        $flixRegistry = $this->getMockForAbstractClass(Registry::class);

        $registry = new AvroSchemaRegistry($flixRegistry);

        $registry->getBodySchemaForTopic('test');
    }

    #[Test]
    public function get_body_schema_for_topic_with_mapping_with_definition(): void
    {
        $definition = $this->getMockBuilder(AvroSchema::class)->disableOriginalConstructor()->getMock();

        $flixRegistry = $this->getMockForAbstractClass(Registry::class);

        $schema = $this->getMockForAbstractClass(KafkaAvroSchemaRegistry::class);
        $schema->expects($this->once())->method('getDefinition')->willReturn($definition);

        $registry = new AvroSchemaRegistry($flixRegistry);

        $registry->addBodySchemaMappingForTopic('test', $schema);

        $this->assertSame($schema, $registry->getBodySchemaForTopic('test'));
    }

    #[Test]
    public function get_key_schema_for_topic_with_mapping_with_definition(): void
    {
        $definition = $this->getMockBuilder(AvroSchema::class)->disableOriginalConstructor()->getMock();

        $flixRegistry = $this->getMockForAbstractClass(Registry::class);

        $schema = $this->getMockForAbstractClass(KafkaAvroSchemaRegistry::class);
        $schema->expects($this->once())->method('getDefinition')->willReturn($definition);

        $registry = new AvroSchemaRegistry($flixRegistry);

        $registry->addKeySchemaMappingForTopic('test2', $schema);

        $this->assertSame($schema, $registry->getKeySchemaForTopic('test2'));
    }

    #[Test]
    public function get_body_schema_for_topic_with_mapping_without_definition_latest(): void
    {
        $definition = $this->getMockBuilder(AvroSchema::class)->disableOriginalConstructor()->getMock();

        $flixRegistry = $this->getMockForAbstractClass(Registry::class);
        $flixRegistry->expects($this->once())->method('latestVersion')->with('test-schema')->willReturn($definition);

        $schema = $this->getMockForAbstractClass(KafkaAvroSchemaRegistry::class);
        $schema->expects($this->once())->method('getDefinition')->willReturn(null);
        $schema->expects($this->once())->method('getVersion')->willReturn(KafkaAvroSchemaRegistry::LATEST_VERSION);
        $schema->expects($this->once())->method('getName')->willReturn('test-schema');
        $schema->expects($this->once())->method('setDefinition')->with($definition);

        $registry = new AvroSchemaRegistry($flixRegistry);

        $registry->addBodySchemaMappingForTopic('test', $schema);

        $registry->getBodySchemaForTopic('test');
    }

    #[Test]
    public function get_body_schema_for_topic_with_mapping_without_definition_version(): void
    {
        $definition = $this->getMockBuilder(AvroSchema::class)->disableOriginalConstructor()->getMock();

        $flixRegistry = $this->getMockForAbstractClass(Registry::class);
        $flixRegistry->expects($this->once())->method('schemaForSubjectAndVersion')->with('test-schema', 1)->willReturn($definition);

        $schema = $this->getMockForAbstractClass(KafkaAvroSchemaRegistry::class);
        $schema->expects($this->once())->method('getDefinition')->willReturn(null);
        $schema->expects($this->exactly(2))->method('getVersion')->willReturn(1);
        $schema->expects($this->once())->method('getName')->willReturn('test-schema');
        $schema->expects($this->once())->method('setDefinition')->with($definition);

        $registry = new AvroSchemaRegistry($flixRegistry);

        $registry->addBodySchemaMappingForTopic('test', $schema);

        $registry->getBodySchemaForTopic('test');
    }

    #[Test]
    public function get_topic_schema_mapping(): void
    {
        $flixRegistry = $this->getMockForAbstractClass(Registry::class);

        $registry = new AvroSchemaRegistry($flixRegistry);

        $this->assertIsArray($registry->getTopicSchemaMapping());
    }
}
