<?php

declare(strict_types=1);

namespace Manyou\Mango\Bundle;

use Doctrine\Migrations\Provider\SchemaProvider as SchemaProviderInterface;
use Manyou\Mango\DependencyInjection\DoctrineConnectionPass;
use Manyou\Mango\DependencyInjection\DoctrineEnableSavepointPass;
use Manyou\Mango\DependencyInjection\DoctrineMigrationsDependencyPass;
use Manyou\Mango\DependencyInjection\DoctrineTypePass;
use Manyou\Mango\DependencyInjection\MessengerMiddlewarePass;
use Manyou\Mango\DependencyInjection\MonologChannelPass;
use Manyou\Mango\Doctrine\Contract\TableProvider;
use Manyou\Mango\Doctrine\SchemaProvider;
use Manyou\Mango\Doctrine\Type\LogLevelType;
use Manyou\Mango\Doctrine\Type\UlidType;
use Manyou\Mango\Doctrine\Type\UsDateTimeImmutableType;
use Manyou\Mango\Doctrine\Type\UuidType;
use Manyou\Mango\MessageLoop\MessageLoopInterface;
use Manyou\Mango\MessageLoop\Messenger\Middleware\MessageLoopMiddleware;
use Manyou\Mango\TaskQueue\Doctrine\Type\TaskStatusType;
use Manyou\Mango\TaskQueue\Messenger\Middleware\TaskQueueMiddware;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class MangoBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        $container
            ->registerForAutoconfiguration(TableProvider::class)
            ->addTag('mango.doctrine.table_provider');

        $container
            ->registerForAutoconfiguration(MessageLoopInterface::class)
            ->addTag('mango.message_loop');

        $container->addCompilerPass(
            new MessengerMiddlewarePass(['id' => TaskQueueMiddware::class]),
            priority: 1,
        );

        $container->addCompilerPass(
            new MessengerMiddlewarePass(['id' => MessageLoopMiddleware::class]),
            priority: 2,
        );

        $container->addCompilerPass(
            new DoctrineTypePass([
                TaskStatusType::NAME => TaskStatusType::class,
                LogLevelType::NAME => LogLevelType::class,
                'ulid' => UlidType::class,
                'uuid' => UuidType::class,
                UsDateTimeImmutableType::NAME => UsDateTimeImmutableType::class,
            ]),
            priority: 1,
        );

        $container->addCompilerPass(
            new DoctrineMigrationsDependencyPass([SchemaProviderInterface::class => SchemaProvider::class]),
        );

        $container->addCompilerPass(
            new MonologChannelPass(
                ['task'],
                ['monolog.handler.task_queue' => 'task'],
            ),
            priority: 1,
        );

        $container->addCompilerPass(
            new DoctrineConnectionPass(['logging' => 'doctrine.dbal.logging_connection']),
            priority: 1,
        );

        $container->addCompilerPass(new DoctrineEnableSavepointPass());
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import(__DIR__ . '/Resources/config/services.php');
    }

    public function getPath(): string
    {
        return __DIR__;
    }
}