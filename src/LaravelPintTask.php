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
            'auto_git_stage' => false,
            'triggered_by' => ['php'],
            'ignore_patterns' => [],
        ]);

        $resolver->addAllowedTypes('config', ['null', 'string']);
        $resolver->addAllowedTypes('preset', ['null', 'string']);
        $resolver->addAllowedTypes('auto_git_stage', ['boolean']);
        $resolver->addAllowedTypes('triggered_by', ['array']);
        $resolver->addAllowedTypes('ignore_patterns', ['array']);

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
        foreach ($config['ignore_patterns'] as $pattern) {
            $files = $files->notPath($pattern);
        }

        if (0 === \count($files)) {
            return TaskResult::createSkipped($this, $context);
        }

        $arguments = $this->processBuilder->createArgumentsForCommand('pint');
        $arguments->addOptionalArgument('--config=%s', $config['config']);
        $arguments->addOptionalArgument('--preset=%s', $config['preset']);

        return $config['auto_git_stage'] ? $this->runWithAutoStage($context, $arguments, $files) : $this->runWithoutAutoStage($context, $arguments, $files);
    }

    public function runWithoutAutoStage($context, $arguments, $files)
    {
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

    public function runWithAutoStage($context, $arguments, $files)
    {
        $arguments->addFiles($files);
        $process = $this->processBuilder->buildProcess($arguments);

        $process->run();

        if (!$process->isSuccessful()) {
            return TaskResult::createFailed($this, $context, $this->formatter->format($process));
        }

        return $this->runGitAddProcess($context, $files);
    }

    public function runGitAddProcess($context, $files)
    {
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
