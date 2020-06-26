<?php

namespace Hrodic\PHPQAPHPStanAnalyzer;

use Edge\QA\OutputMode;
use Edge\QA\Tools\Tool;
use Nette\Neon\Neon;

class PHPStan extends Tool
{
    public static $SETTINGS = array(
        'optionSeparator' => ' ',
        'internalClass' => 'Hrodic\PHPQAPHPStanAnalyzer\PHPStan',
        'outputMode' => OutputMode::XML_CONSOLE_OUTPUT,
        'xml' => ['phpstan.xml'],
        'errorsXPath' => '//checkstyle/file/error',
        'composer' => 'phpstan/phpstan',
        'internalDependencies' => [
            'nette/neon' => 'Nette\Neon\Neon',
        ],
    );

    public function __invoke()
    {
        $createAbsolutePaths = function (array $relativeDirs) {
            return array_values(array_filter(array_map(
                function ($relativeDir) {
                    return '%currentWorkingDirectory%/' . trim($relativeDir, '"');
                },
                $relativeDirs
            )));
        };

        $defaultConfig = $this->config->path('phpstan.standard') ?: (getcwd() . '/phpstan.neon');
        if (file_exists($defaultConfig)) {
            $config = Neon::decode(file_get_contents($defaultConfig));
            $config['parameters'] += [
                'excludes_analyse' => [],
            ];
        } else {
            $config = [
                'parameters' => [
                    'scanDirectories' => $createAbsolutePaths($this->options->getAnalyzedDirs()),
                    'excludes_analyse' => [],
                ],
            ];
        }

        $config['parameters']['excludes_analyse'] = array_merge(
            $config['parameters']['excludes_analyse'],
            $createAbsolutePaths($this->options->ignore->phpstan())
        );

        $phpstanConfig = "# Configuration generated in phpqa\n" . Neon::encode($config);
        $neonFile = $this->saveDynamicConfig($phpstanConfig, 'neon');

        return [
            'analyze',
            'ansi' => '',
            'error-format' => 'checkstyle',
            'level' => $this->config->value('phpstan.level'),
            'configuration' => $neonFile,
            $this->options->getAnalyzedDirs(' '),
        ];
    }
}