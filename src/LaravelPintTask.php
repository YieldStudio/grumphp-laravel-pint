<?php

declare(strict_types=1);

namespace YieldStudio\GrumPHPLaravelPint;

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
            'triggered_by' => ['php'],
        ]);

        $resolver->addAllowedTypes('config', ['null', 'string']);
        $resolver->addAllowedTypes('triggered_by', ['array']);

        return $resolver;
    }

    public function canRunInContext(ContextInterface $context): bool
    {
        return $context instanceof GitPreCommitContext || $context instanceof RunContext;
    }

    public function runProcess(Process $process, ContextInterface $context): ?TaskResult
    {
        $process->run();

        if (! $process->isSuccessful()) {
            return TaskResult::createFailed($this, $context, $this->formatter->format($process));
        }

        return null;
    }

    public function run(ContextInterface $context): TaskResultInterface
    {
        $config = $this->getConfig()->getOptions();
        if (! ($context instanceof GitPreCommitContext)) {
            $arguments = $this->processBuilder->createArgumentsForCommand('pint');
            $arguments->addOptionalArgument('--config=%s', $config['config']);

            $result = $this->runProcess($this->processBuilder->buildProcess($arguments), $context);
            if ($result) {
                return $result;
            }

            return TaskResult::createPassed($this, $context);
        }

        $files = $context->getFiles()->extensions($config['triggered_by']);
        if (0 === \count($files)) {
            return TaskResult::createSkipped($this, $context);
        }

        $arguments = $this->processBuilder->createArgumentsForCommand('pint');
        $arguments->addOptionalArgument('--config=%s', $config['config']);

        $arguments->addFiles($files);
        $process = $this->processBuilder->buildProcess($arguments);

        $result = $this->runProcess($process, $context);
        if ($result) {
            return $result;
        }

        $gitArgs = $this->processBuilder->createArgumentsForCommand('git');
        $gitArgs->add('add');
        $gitArgs->addFiles($files);

        $gitProcess = $this->processBuilder->buildProcess($gitArgs);
        $gitProcess->run();

        if (! $gitProcess->isSuccessful()) {
            return TaskResult::createFailed($this, $context, $this->formatter->format($process));
        }

        return TaskResult::createPassed($this, $context);
    }
}
