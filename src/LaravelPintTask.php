<?php

declare(strict_types=1);

namespace YieldStudio\GrumPHPLaravelPint;

use GrumPHP\Fixer\Provider\FixableProcessResultProvider;
use GrumPHP\Runner\TaskResult;
use GrumPHP\Runner\TaskResultInterface;
use GrumPHP\Task\AbstractExternalTask;
use GrumPHP\Task\Context\ContextInterface;
use GrumPHP\Task\Context\GitPreCommitContext;
use GrumPHP\Task\Context\RunContext;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\Process;

class LaravelPintTask extends AbstractExternalTask
{
    public function getName(): string
    {
        return 'laravel_pint';
    }

    public static function getConfigurableOptions(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'config' => null,
            'preset' => null,
            'triggered_by' => ['php'],
        ]);

        $resolver->addAllowedTypes('config', ['null', 'string']);
        $resolver->addAllowedTypes('preset', ['null', 'string']);
        $resolver->addAllowedTypes('triggered_by', ['array']);

        return $resolver;
    }

    public function canRunInContext(ContextInterface $context): bool
    {
        return $context instanceof GitPreCommitContext || $context instanceof RunContext;
    }

    public function run(ContextInterface $context): TaskResultInterface
    {
        $config = $this->getConfig()->getOptions();
        $files = $context->getFiles()->extensions($config['triggered_by']);
        if (0 === \count($files)) {
            return TaskResult::createSkipped($this, $context);
        }

        $arguments = $this->processBuilder->createArgumentsForCommand('pint');
        $arguments->addOptionalArgument('--config=%s', $config['config']);
        $arguments->addOptionalArgument('--preset=%s', $config['preset']);
        $arguments->add('--test');
        $arguments->add('-v');

        $arguments->addFiles($files);
        $process = $this->processBuilder->buildProcess($arguments);

        $process->run();

        if (!$process->isSuccessful()) {
            return FixableProcessResultProvider::provide(
                TaskResult::createFailed($this, $context, $this->formatter->format($process)),
                function () use ($arguments): Process {
                    $arguments->removeElement('--test');
                    return $this->processBuilder->buildProcess($arguments);
                }
            );
        }

        return TaskResult::createPassed($this, $context);
    }
}
