<?php

declare(strict_types=1);

namespace WebAuditKit\Result;

final class AuditResult
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $checks;

    /**
     * @param array<string, array<string, mixed>> $checks
     */
    public function __construct(
        private readonly ?string $url = null,
        array $checks = []
    ) {
        $this->checks = $checks;
    }

    /**
     * Return the URL associated with this audit.
     */
    public function url(): ?string
    {
        return $this->url;
    }

    /**
     * Return all audit checks.
     *
     * @return array<string, array<string, mixed>>
     */
    public function checks(): array
    {
        return $this->checks;
    }

    /**
     * Determine whether a check exists.
     */
    public function hasCheck(string $name): bool
    {
        return array_key_exists(
            $name,
            $this->checks
        );
    }

    /**
     * Return a specific audit check.
     *
     * @return array<string, mixed>|null
     */
    public function check(string $name): ?array
    {
        return $this->checks[$name] ?? null;
    }

    /**
     * Add or replace an audit check.
     *
     * @param array<string, mixed> $result
     */
    public function addCheck(
        string $name,
        array $result
    ): self {
        $this->checks[$name] = $result;

        return $this;
    }

    /**
     * Return all checks with pass status.
     *
     * @return array<string, array<string, mixed>>
     */
    public function passed(): array
    {
        return $this->filterByStatus('pass');
    }

    /**
     * Return all checks with warning status.
     *
     * @return array<string, array<string, mixed>>
     */
    public function warnings(): array
    {
        return $this->filterByStatus('warning');
    }

    /**
     * Return all checks with error status.
     *
     * @return array<string, array<string, mixed>>
     */
    public function errors(): array
    {
        return $this->filterByStatus('error');
    }

    /**
     * Return the number of passed checks.
     */
    public function passedCount(): int
    {
        return count($this->passed());
    }

    /**
     * Return the number of warning checks.
     */
    public function warningCount(): int
    {
        return count($this->warnings());
    }

    /**
     * Return the number of error checks.
     */
    public function errorCount(): int
    {
        return count($this->errors());
    }

    /**
     * Return the total number of checks.
     */
    public function totalCount(): int
    {
        return count($this->checks);
    }

    /**
     * Determine the overall audit status.
     *
     * Severity priority:
     *
     * error > warning > pass
     */
    public function status(): string
    {
        if ($this->errorCount() > 0) {
            return 'error';
        }

        if ($this->warningCount() > 0) {
            return 'warning';
        }

        return 'pass';
    }

    /**
     * Return a summary of audit check counts.
     *
     * @return array{
     *     total: int,
     *     passed: int,
     *     warnings: int,
     *     errors: int,
     *     status: string
     * }
     */
    public function summary(): array
    {
        return [
            'total' => $this->totalCount(),
            'passed' => $this->passedCount(),
            'warnings' => $this->warningCount(),
            'errors' => $this->errorCount(),
            'status' => $this->status(),
        ];
    }

    /**
     * Determine whether the audit contains errors.
     */
    public function hasErrors(): bool
    {
        return $this->errorCount() > 0;
    }

    /**
     * Determine whether the audit contains warnings.
     */
    public function hasWarnings(): bool
    {
        return $this->warningCount() > 0;
    }

    /**
     * Determine whether all recorded checks passed.
     */
    public function hasPassed(): bool
    {
        return !$this->hasErrors()
            && !$this->hasWarnings();
    }

    /**
     * Convert the audit result to a structured array.
     *
     * @return array{
     *     url: ?string,
     *     status: string,
     *     summary: array{
     *         total: int,
     *         passed: int,
     *         warnings: int,
     *         errors: int,
     *         status: string
     *     },
     *     checks: array<string, array<string, mixed>>
     * }
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'status' => $this->status(),
            'summary' => $this->summary(),
            'checks' => $this->checks,
        ];
    }

    /**
     * Filter checks by their status.
     *
     * Checks without a recognized status are intentionally excluded
     * from pass/warning/error collections.
     *
     * @return array<string, array<string, mixed>>
     */
    private function filterByStatus(string $status): array
    {
        return array_filter(
            $this->checks,
            static function (array $check) use ($status): bool {
                return isset($check['status'])
                    && $check['status'] === $status;
            }
        );
    }
}
