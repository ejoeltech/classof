<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @param array<string, mixed> $data */
    private function __construct(private array $data)
    {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $rules
     * @return array<string, string>
     */
    public static function validate(array $data, array $rules): array
    {
        $validator = new self($data);

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;

            foreach (explode('|', $ruleString) as $rule) {
                if ($rule === '') {
                    continue;
                }

                [$name, $argument] = array_pad(explode(':', $rule, 2), 2, '');

                $validator->apply($field, $value, $name, $argument);

                if (isset($validator->errors[$field])) {
                    break;
                }
            }
        }

        return $validator->errors;
    }

    private function apply(string $field, mixed $value, string $rule, string $argument): void
    {
        $label = ucfirst(str_replace(['_', '.'], ' ', $field));
        $stringValue = is_scalar($value) ? trim((string) $value) : '';
        $isEmpty = $value === null || $value === '' || (is_array($value) && $value === []);

        if ($rule === 'required') {
            if ($isEmpty) {
                $this->add($field, $label . ' is required.');
            }

            return;
        }

        if ($isEmpty) {
            return;
        }

        switch ($rule) {
            case 'email':
                if (!filter_var($stringValue, FILTER_VALIDATE_EMAIL)) {
                    $this->add($field, 'Enter a valid email address.');
                }
                break;

            case 'numeric':
                if (!is_numeric($value)) {
                    $this->add($field, $label . ' must be a number.');
                }
                break;

            case 'integer':
                if (!preg_match('/^-?\d+$/', $stringValue)) {
                    $this->add($field, $label . ' must be a whole number.');
                }
                break;

            case 'min':
                if (mb_strlen($stringValue) < (int) $argument) {
                    $this->add($field, sprintf('%s must be at least %d characters.', $label, (int) $argument));
                }
                break;

            case 'max':
                if (mb_strlen($stringValue) > (int) $argument) {
                    $this->add($field, sprintf('%s may not be longer than %d characters.', $label, (int) $argument));
                }
                break;

            case 'gt':
                if (!is_numeric($value) || (float) $value <= (float) $argument) {
                    $this->add($field, sprintf('%s must be greater than %s.', $label, $argument));
                }
                break;

            case 'gte':
                if (!is_numeric($value) || (float) $value < (float) $argument) {
                    $this->add($field, sprintf('%s must be at least %s.', $label, $argument));
                }
                break;

            case 'confirmed':
                if ($stringValue !== (string) ($this->data[$field . '_confirmation'] ?? '')) {
                    $this->add($field, 'The confirmation does not match.');
                }
                break;

            case 'date':
                if (strtotime($stringValue) === false) {
                    $this->add($field, $label . ' must be a valid date.');
                }
                break;

            case 'in':
                if (!in_array($stringValue, explode(',', $argument), true)) {
                    $this->add($field, $label . ' is invalid.');
                }
                break;

            case 'url':
                if (!filter_var($stringValue, FILTER_VALIDATE_URL)) {
                    $this->add($field, 'Enter a valid URL.');
                }
                break;

            case 'same':
                if ($stringValue !== (string) ($this->data[$argument] ?? '')) {
                    $this->add($field, $label . ' does not match.');
                }
                break;

            case 'unique':
                $this->validateUnique($field, $stringValue, $argument);
                break;

            case 'exists':
                [$table, $column] = array_pad(explode(',', $argument), 2, 'id');
                $count = Database::value(
                    sprintf('SELECT COUNT(*) FROM `%s` WHERE `%s` = ?', $table, $column),
                    [$stringValue],
                    0
                );
                if ((int) $count === 0) {
                    $this->add($field, $label . ' is not valid.');
                }
                break;
        }
    }

    private function validateUnique(string $field, string $value, string $argument): void
    {
        $parts = explode(',', $argument);
        $table = $parts[0] ?? '';
        $column = $parts[1] ?? $field;
        $ignoreId = isset($parts[2]) && is_numeric($parts[2]) ? (int) $parts[2] : null;

        $sql = sprintf('SELECT COUNT(*) FROM `%s` WHERE `%s` = ?', $table, $column);
        $params = [$value];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }

        if ((int) Database::value($sql, $params, 0) > 0) {
            $this->add($field, 'That ' . strtolower(str_replace('_', ' ', $field)) . ' is already taken.');
        }
    }

    private function add(string $field, string $message): void
    {
        $this->errors[$field] ??= $message;
    }
}
