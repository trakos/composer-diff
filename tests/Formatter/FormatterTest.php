<?php

namespace IonBazan\ComposerDiff\Tests\Formatter;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Package\PackageInterface;
use IonBazan\ComposerDiff\Formatter\Formatter;
use IonBazan\ComposerDiff\Tests\TestCase;
use IonBazan\ComposerDiff\Url\GeneratorContainer;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

abstract class FormatterTest extends TestCase
{
    public function testItNoopsWhenListIsEmpty()
    {
        $output = new StreamOutput(fopen('php://memory', 'wb', false));
        $formatter = $this->getFormatter($output, $this->getGenerators());
        $formatter->render(array(), array(), true);
        $this->assertSame(static::getEmptyOutput(), $this->getDisplay($output));
    }

    public function testGetUrlReturnsNullForInvalidOperation()
    {
        $output = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')->getMock();
        $operation = $this->getMockBuilder('Composer\DependencyResolver\Operation\OperationInterface')->getMock();
        $formatter = $this->getFormatter($output, $this->getGenerators());
        $this->assertNull($formatter->getUrl($operation));
    }

    /**
     * @param bool $withUrls
     *
     * @testWith   [false]
     *             [true]
     */
    public function testItRendersTheListOfOperations($withUrls)
    {
        $output = new StreamOutput(fopen('php://memory', 'wb', false));
        $formatter = $this->getFormatter($output, $this->getGenerators());
        $prodPackages = array(
            new InstallOperation($this->getPackage('a/package-1', '1.0.0')),
            new InstallOperation($this->getPackage('a/no-link-1', '1.0.0')),
            new UpdateOperation($this->getPackage('a/package-2', '1.0.0'), $this->getPackage('a/package-2', '1.2.0')),
            new UpdateOperation($this->getPackage('a/package-3', '2.0.0'), $this->getPackage('a/package-3', '1.1.1')),
            new UpdateOperation($this->getPackage('a/no-link-2', '2.0.0'), $this->getPackage('a/no-link-2', '1.1.1')),
            new UpdateOperation($this->getPackage('php', '>=7.4.6'), $this->getPackage('php', '^8.0')),
        );
        $devPackages = array(
            new UpdateOperation($this->getPackage('a/package-5', 'dev-master', 'dev-master 1234567'), $this->getPackage('a/package-5', '1.1.1')),
            new UninstallOperation($this->getPackage('a/package-4', '0.1.1')),
            new UninstallOperation($this->getPackage('a/no-link-2', '0.1.1')),
        );
        $formatter->render($prodPackages, $devPackages, $withUrls);
        $this->assertSame($this->getSampleOutput($withUrls), $this->getDisplay($output));
    }

    public function testItFailsWithInvalidOperation()
    {
        $output = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')->getMock();
        $this->setExpectedException('InvalidArgumentException', 'Invalid operation');
        $this->getFormatter($output, $this->getGenerators())->render(array(
            $this->getMockBuilder('Composer\DependencyResolver\Operation\OperationInterface')->getMock(),
        ), array(), false);
    }

    /**
     * @return Formatter
     */
    abstract protected function getFormatter(OutputInterface $output, GeneratorContainer $generators);

    /**
     * @param bool $withUrls
     *
     * @return string
     */
    abstract protected function getSampleOutput($withUrls);

    /**
     * @return string
     */
    protected static function getEmptyOutput()
    {
        return '';
    }

    /**
     * @return false|string
     */
    protected function getDisplay(OutputInterface $output)
    {
        rewind($output->getStream());

        return stream_get_contents($output->getStream());
    }

    /**
     * @return MockObject&GeneratorContainer
     */
    protected function getGenerators()
    {
        $generator = $this->getMockBuilder('IonBazan\ComposerDiff\Url\UrlGenerator')->getMock();
        $generator->method('getCompareUrl')->willReturnCallback(function (PackageInterface $base, PackageInterface $target) {
            return sprintf('https://example.com/c/%s..%s', $base->getVersion(), $target->getVersion());
        });
        $generator->method('getReleaseUrl')->willReturnCallback(function (PackageInterface $package) {
            return sprintf('https://example.com/r/%s', $package->getVersion());
        });

        $generators = $this->getMockBuilder('IonBazan\ComposerDiff\Url\GeneratorContainer')
            ->disableOriginalConstructor()
            ->getMock();
        $generators->method('get')
            ->willReturnCallback(function (PackageInterface $package) use ($generator) {
                if ('php' === $package->getName() || false !== strpos($package->getName(), 'a/no-link')) {
                    return null;
                }

                return $generator;
            });

        return $generators;
    }
}
