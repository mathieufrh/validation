<?php
/**
 * Copyright 2020 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use LaravelJsonApi\Validation\JsonApiValidation;

abstract class AbstractAllowedRule implements Rule
{

    /**
     * @var Collection
     */
    private Collection $allowed;

    /**
     * The last value that was validated.
     *
     * @var mixed|null
     */
    private $value;

    /**
     * Extract parameters from the value.
     *
     * @param mixed $value
     * @return Collection
     */
    abstract protected function extract($value): Collection;

    /**
     * AllowedFilterParameters constructor.
     *
     * @param iterable $allowed
     */
    public function __construct(iterable $allowed = [])
    {
        $values = collect($allowed)->values();
        $this->allowed = $values->combine($values);
        $this->all = false;
    }

    /**
     * Add allowed parameters.
     *
     * @param string ...$params
     * @return $this
     */
    public function allow(string ...$params): self
    {
        foreach ($params as $param) {
            $this->allowed->put($param, $param);
        }

        return $this;
    }

    /**
     * Forget an allowed parameter.
     *
     * @param string ...$params
     * @return $this
     */
    public function forget(string ...$params): self
    {
        $this->allowed->forget($params);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function passes($attribute, $value)
    {
        $this->value = $value;

        return $this->extract($value)->every(function ($key) {
            return $this->allowed($key);
        });
    }

    /**
     * @inheritDoc
     */
    public function message()
    {
        $namespace = JsonApiValidation::$translationNamespace;
        $name = Str::snake(class_basename($this));
        $invalid = $this->invalid();

        if ($invalid->isEmpty()) {
            $key = 'default';
        } else {
            $key = (1 === $invalid->count()) ? 'singular' : 'plural';
        }

        return trans("{$namespace}::validation.{$name}.{$key}", [
            'values' => $params = $invalid->implode(', '),
        ]);
    }

    /**
     * Is the parameter allowed?
     *
     * @param string $param
     * @return bool
     */
    protected function allowed(string $param): bool
    {
        return $this->allowed->has($param);
    }

    /**
     * @return Collection
     */
    protected function invalid(): Collection
    {
        return $this
            ->extract($this->value)
            ->reject(fn($value) => $this->allowed($value))
            ->sort();
    }

}
