<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Class untuk menampung hasil validasi
 */
class ValidationResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly array $errors = []
    ) {
    }

    /**
     * Factory method untuk hasil sukses
     */
    public static function ok(): self
    {
        return new self(true, []);
    }

    /**
     * Factory method untuk hasil gagal dengan error
     */
    public static function fail(array $errors): self
    {
        return new self(false, $errors);
    }

    /**
     * Factory method untuk hasil gagal dengan single error
     */
    public static function failSingle(string $error): self
    {
        return new self(false, [$error]);
    }

    /**
     * Check apakah validasi berhasil
     */
    public function isValid(): bool
    {
        return $this->ok;
    }

    /**
     * Check apakah validasi gagal
     */
    public function hasErrors(): bool
    {
        return !$this->ok;
    }

    /**
     * Get first error message
     */
    public function getFirstError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    /**
     * Get all error messages
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get error messages as formatted string
     */
    public function getErrorsAsString(string $separator = '; '): string
    {
        return implode($separator, $this->errors);
    }
}