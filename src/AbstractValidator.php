<?php
declare(strict_types=1);

namespace Harakiri\Validator;

use Harakiri\Validator\Contracts\ValidatorInterface;
use Harakiri\Validator\Exceptions\ValidatorException;
use Illuminate\Support\MessageBag;

/**
 * Class AbstractValidator
 * @package Harakiri\Validator
 */
abstract class AbstractValidator implements ValidatorInterface
{
    /**
     * @var int
     */
    protected $id = null;

    /**
     * @var
     */
    protected $validator;

    /**
     * Data to be validated
     *
     * @var array
     */
    protected $data = [];

    /**
     * Validation Rules
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Validation Custom Messages
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Validation Custom Attributes
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Validation errors
     *
     * @var MessageBag
     */
    protected $errors = [];


    /**
     * Set Id
     *
     * @param $id
     * @return $this
     */
    public function setId($id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Set data to validate
     *
     * @param array $data
     * @return $this
     */
    public function with(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Return errors
     *
     * @return array
     */
    public function errors(): array
    {
        return $this->errorsBag()->all();
    }

    /**
     * Errors
     *
     * @return MessageBag
     */
    public function errorsBag()
    {
        return $this->errors;
    }

    /**
     * Pass the data and the rules to the validator
     *
     * @param string $action
     * @return boolean
     */
    abstract public function passes($action = null): bool;

    /**
     * Pass the data and the rules to the validator or throws ValidatorException
     *
     * @throws ValidatorException
     * @param string $action
     * @return boolean
     */
    public function passesOrFail($action = null): bool
    {
        if (!$this->passes($action)) {
            throw new ValidatorException($this->errorsBag());
        }

        return true;
    }

    /**
     * Get rule for validation by action ValidatorInterface::RULE_CREATE or ValidatorInterface::RULE_UPDATE
     *
     * Default rule: ValidatorInterface::RULE_CREATE
     *
     * @param null $action
     * @return array
     */
    public function getRules($action = null): array
    {
        $rules = $this->rules;

        if (isset($this->rules[$action])) {
            $rules = $this->rules[$action];
        }

        return $this->parserValidationRules($rules, $this->id);
    }

    /**
     * Set Rules for Validation
     *
     * @param array $rules
     * @return $this
     */
    public function setRules(array $rules): self
    {
        $this->rules = $rules;
        return $this;
    }

    /**
     * Get Custom error messages for validation
     *
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Set Custom error messages for Validation
     *
     * @param array $messages
     * @return $this
     */
    public function setMessages(array $messages): self
    {
        $this->messages = $messages;
        return $this;
    }

    /**
     * Get Custom error attributes for validation
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Set Custom error attributes for Validation
     *
     * @param array $attributes
     * @return $this
     */
    public function setAttributes(array $attributes): self
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * Parser Validation Rules
     *
     * @param $rules
     * @param null $id
     * @return array
     */
    protected function parserValidationRules($rules, $id = null): array
    {
        if (null === $id) {
            return $rules;
        }

        array_walk($rules, function (&$rules, $field) use ($id) {
            if (!is_array($rules)) {
                $rules = explode('|', $rules);
            }

            foreach ($rules as $ruleIdx => $rule) {
                // get name and parameters
                @list($name, $params) = array_pad(explode(':', $rule), 2, null);

                // only do someting for the unique rule
                if (strtolower($name) !== 'unique') {
                    continue; // continue in foreach loop, nothing left to do here
                }

                $p = array_map('trim', explode(',', $params));

                // set field name to rules key ($field) (laravel convention)
                if (!isset($p[1])) {
                    $p[1] = $field;
                }

                // set 3rd parameter to id given to getValidationRules()
                $p[2] = $id;

                $params = implode(',', $p);
                $rules[$ruleIdx] = $name . ':' . $params;
            }
        });

        return $rules;
    }
}
