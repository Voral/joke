<?php

declare(strict_types=1);

namespace Vasoft\Joke\Tests\Container;

use PHPUnit\Framework\TestCase;
use Vasoft\Joke\Core\ServiceContainer;
use Vasoft\Joke\Contract\Core\Routing\ResolverInterface;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\Joke\Core\BaseContainer
 */
final class ServiceNameValidationTest extends TestCase
{
    private array $warnings = [];

    protected function setUp(): void
    {
        $this->warnings = [];
        set_error_handler(function ($errno, $errstr): void {
            if (E_USER_WARNING === $errno) {
                $this->warnings[] = $errstr;
            }
        });
    }

    public function testRegisterWithValidInterface(): void
    {
        $container = new ServiceContainer();
        $container->register(ResolverInterface::class, \stdClass::class);
        restore_error_handler();
        self::assertEmpty($this->warnings);
    }

    public function testRegisterWithValidClass(): void
    {
        $container = new ServiceContainer();
        $container->register(\DateTime::class, static fn() => new \DateTime());
        restore_error_handler();
        self::assertEmpty($this->warnings);
    }

    public function testRegisterWithNonExistentFqcnTriggersWarning(): void
    {
        $container = new ServiceContainer();
        $container->register('Non\Existent\Class', \stdClass::class);

        restore_error_handler();

        self::assertNotEmpty($this->warnings);
        self::assertStringContainsString('Non\Existent\Class', $this->warnings[0]);
    }

    public function testRegisterWithArbitraryNameDoesNotTriggerWarning(): void
    {
        $container = new ServiceContainer();
        $container->register('logger', \stdClass::class);

        restore_error_handler();

        self::assertEmpty($this->warnings);
    }

    public function testRegisterAliasWithNonExistentConcreteTriggersWarning(): void
    {
        $container = new ServiceContainer();
        $container->registerAlias('my_service', 'Non\Existent\Target');

        restore_error_handler();

        self::assertNotEmpty($this->warnings);
        self::assertStringContainsString(
            'Service name \'Non\Existent\Target\' is not a valid class or interface.',
            $this->warnings[0],
        );
    }
}
