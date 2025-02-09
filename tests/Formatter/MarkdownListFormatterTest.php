<?php

namespace IonBazan\ComposerDiff\Tests\Formatter;

use IonBazan\ComposerDiff\Formatter\MarkdownListFormatter;
use IonBazan\ComposerDiff\Url\GeneratorContainer;
use Symfony\Component\Console\Output\OutputInterface;

class MarkdownListFormatterTest extends FormatterTest
{
    protected function getSampleOutput($withUrls)
    {
        if ($withUrls) {
            return <<<OUTPUT
Prod Packages
=============

 - Install a/package-1 (1.0.0) [Compare](https://example.com/r/1.0.0)
 - Install a/no-link-1 (1.0.0) 
 - Upgrade a/package-2 (1.0.0 => 1.2.0) [Compare](https://example.com/c/1.0.0..1.2.0)
 - Downgrade a/package-3 (2.0.0 => 1.1.1) [Compare](https://example.com/c/2.0.0..1.1.1)
 - Downgrade a/no-link-2 (2.0.0 => 1.1.1) 
 - Upgrade php (>=7.4.6 => ^8.0) 

Dev Packages
============

 - Downgrade a/package-5 (dev-master 1234567 => 1.1.1) [Compare](https://example.com/c/dev-master..1.1.1)
 - Uninstall a/package-4 (0.1.1) [Compare](https://example.com/r/0.1.1)
 - Uninstall a/no-link-2 (0.1.1) 


OUTPUT;
        }

        return <<<OUTPUT
Prod Packages
=============

 - Install a/package-1 (1.0.0)
 - Install a/no-link-1 (1.0.0)
 - Upgrade a/package-2 (1.0.0 => 1.2.0)
 - Downgrade a/package-3 (2.0.0 => 1.1.1)
 - Downgrade a/no-link-2 (2.0.0 => 1.1.1)
 - Upgrade php (>=7.4.6 => ^8.0)

Dev Packages
============

 - Downgrade a/package-5 (dev-master 1234567 => 1.1.1)
 - Uninstall a/package-4 (0.1.1)
 - Uninstall a/no-link-2 (0.1.1)


OUTPUT;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormatter(OutputInterface $output, GeneratorContainer $generators)
    {
        return new MarkdownListFormatter($output, $generators);
    }
}
