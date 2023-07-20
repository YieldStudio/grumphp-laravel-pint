<?php

declare(strict_types=1);

namespace YieldStudio\GrumPHPLaravelPint;

use GrumPHP\Collection\FilesCollection;
use GrumPHP\Collection\ProcessArgumentsCollection;
use GrumPHP\Fixer\Provider\FixableProcessProvider;
use GrumPHP\Runner\FixableTaskResult;
use GrumPHP\Runner\TaskResult;
use GrumPHP\Runner\TaskResultInterface;
use GrumPHP\Task\AbstractExternalTask;
use GrumPHP\Task\Config\ConfigOptionsResolver;
use GrumPHP\Task\Context\ContextInterface;
use GrumPHP\Task\Context\GitPreCommitContext;
use GrumPHP\Task\Context\RunContext;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LaravelPintTask extends AbstractExternalTask
{
    private const CONTEXT_NAME = [
        GitPreCommitContext::class => 'pre_commit',
        RunContext::class => 'run',
    ];

    public function getName(): string
    {
        return 'laravel_pint';
    }

    public static function getConfigurableOptions(): ConfigOptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'config' => null,
            'preset' => null,
            'auto_fix' => 'pre_commit',
            'auto_stage' => 'pre_commit',
            'triggered_by' => ['php'],
            'ignore_patterns' => [],
        ]);

        $resolver->addAllowedTypes('config', ['null', 'string']);
        $resolver->addAllowedTypes('preset', ['null', 'string']);
        $resolver->addAllowedValues('auto_fix', [true, false, 'pre_commit', 'run']);
        $resolver->addAllowedValues('auto_stage', [true, false, 'pre_commit', 'run']);
        $resolver->addAllowedTypes('triggered_by', ['array']);
        $resolver->addAllowedTypes('ignore_patterns', ['array']);

        return ConfigOptionsResolver::fromClosure(
            static fn (array $options): array => $resolver->resolve($options)
        );
    }

    public function canRunInContext(ContextInterface $context): bool
    {
        return $context instanceof GitPreCommitContext || $context instanceof RunContext;
    }

    public function run(ContextInterface $context): TaskResultInterface
    {
        $config = $this->getConfig()->getOptions();
        $files = $this->getFiles($context, $config);

        if (0 === \count($files)) {
            return TaskResult::createSkipped($this, $context);
        }

        $contextName = self::CONTEXT_NAME[get_class($context)];

        $arguments = $this->processBuilder->createArgumentsForCommand('pint');
        $arguments->addOptionalArgument('--config=%s', $config['config']);
        $arguments->addOptionalArgument('--preset=%s', $config['preset']);

        if (in_array($config['auto_fix'], [true, $contextName])) {
            return $this->fixRun($context, $arguments, $files);
        }

        return $this->dryRun($context, $arguments, $files);
    }

    private function dryRun(ContextInterface $context, ProcessArgumentsCollection $arguments, FilesCollection $files): TaskResultInterface
    {
        $arguments->add('--test');
        $arguments->add('-v');

        $arguments->addFiles($files);

        $process = $this->processBuilder->buildProcess($arguments);
        $process->run();

        if (! $process->isSuccessful()) {
            $arguments->removeElement('--test');
            $fixerProcess = $this->processBuilder->buildProcess($arguments);

            return new FixableTaskResult(
                TaskResult::createFailed($this, $context, $this->formatter->format($process)),
                FixableProcessProvider::provide($fixerProcess->getCommandLine())
            );
        }

        return TaskResult::createPassed($this, $context);
    }

    protected function fixRun(ContextInterface $context, ProcessArgumentsCollection $arguments, FilesCollection $files): TaskResultInterface
    {
        $config = $this->getConfig()->getOptions();
        $contextName = self::CONTEXT_NAME[get_class($context)];

        $arguments->addFiles($files);

        $process = $this->processBuilder->buildProcess($arguments);
        $process->run();

        if (! $process->isSuccessful()) {
            return TaskResult::createFailed($this, $context, $this->formatter->format($process));
        }

        if (in_array($config['auto_stage'], [true, $contextName])) {
            return $this->runGitAddProcess($context, $files);
        }

        return TaskResult::createPassed($this, $context);
    }

    protected function runGitAddProcess(ContextInterface $context, FilesCollection $files): TaskResultInterface
    {
        $gitArgs = $this->processBuilder->createArgumentsForCommand('git');
        $gitArgs->add('add');
        $gitArgs->addFiles($files);

        $process = $this->processBuilder->buildProcess($gitArgs);
        $process->run();

        if (! $process->isSuccessful()) {
            return TaskResult::createFailed($this, $context, $this->formatter->format($process));
        }

        return TaskResult::createPassed($this, $context);
    }

    protected function getFiles(ContextInterface $context, array $config): FilesCollection
    {
        $files = $context->getFiles()->extensions($config['triggered_by']);
        foreach ($config['ignore_patterns'] as $pattern) {
            $files = $files->notPath($pattern);
        }

        return $files;
    }
}
