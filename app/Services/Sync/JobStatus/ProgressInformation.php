<?php

declare(strict_types=1);

namespace App\Services\Sync\JobStatus;

/**
 * Trait ProgressInformation.
 */
trait ProgressInformation
{
    /** @var array */
    protected $errors;
    /** @var string */
    protected $identifier;
    /** @var array */
    protected $messages;
    /** @var array */
    protected $warnings;

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors ?? [];
    }

    /**
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages ?? [];
    }

    /**
     * @return array
     */
    public function getWarnings(): array
    {
        return $this->warnings ?? [];
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * @param int    $index
     * @param string $error
     */
    protected function addError(int $index, string $error): void
    {
        $this->errors           = $this->errors ?? [];
        $this->errors[$index]   = $this->errors[$index] ?? [];
        $this->errors[$index][] = $error;

        // write errors
        app('log')->error(sprintf('Error: %s %d: %s', $this->identifier, $index, $error));
        JobStatusManager::addError($this->identifier, $index, $error);
    }

    /**
     * @param int    $index
     * @param string $message
     */
    protected function addMessage(int $index, string $message): void
    {
        $this->messages           = $this->messages ?? [];
        $this->messages[$index]   = $this->messages[$index] ?? [];
        $this->messages[$index][] = $message;

        // write message
        app('log')->info(sprintf('Message: %s %d: %s', $this->identifier, $index, $message));
        JobStatusManager::addMessage($this->identifier, $index, $message);
    }

    /**
     * @param int    $index
     * @param string $warning
     */
    protected function addWarning(int $index, string $warning): void
    {
        $this->warnings           = $this->warnings ?? [];
        $this->warnings[$index]   = $this->warnings[$index] ?? [];
        $this->warnings[$index][] = $warning;

        // write warning
        app('log')->warning(sprintf('Warning: %s %d: %s', $this->identifier, $index, $warning));
        JobStatusManager::addWarning($this->identifier, $index, $warning);
    }
}
