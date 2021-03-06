<?php

/**
 * @file
 * Contains \Drupal\rules\Engine\RulesComponent.
 */

namespace Drupal\rules\Engine;

use Drupal\rules\Context\ContextDefinitionInterface;

/**
 * Handles executable Rules components.
 */
class RulesComponent {

  /**
   * The rules execution state.
   *
   * @var \Drupal\rules\Engine\ExecutionStateInterface
   */
  protected $state;

  /**
   * Definitions for the context used by the component.
   *
   * @var \Drupal\rules\Context\ContextDefinitionInterface[]
   */
  protected $contextDefinitions;

  /**
   * List of context names that is provided back to the caller.
   *
   * @var string[]
   */
  protected $providedContext = [];

  /**
   * The expression.
   *
   * @var \Drupal\rules\Engine\ExpressionInterface
   */
  protected $expression;

  /**
   * Constructs the object.
   *
   * @param \Drupal\rules\Engine\ExpressionInterface $expression
   *   The expression of the component.
   *
   * @return static
   */
  public static function create(ExpressionInterface $expression) {
    return new static($expression);
  }

  /**
   * Constructs the object.
   *
   * @param \Drupal\rules\Engine\ExpressionInterface $expression
   *   The expression of the component.
   */
  protected function __construct(ExpressionInterface $expression) {
    $this->state = ExecutionState::create();
    $this->expression = $expression;
  }

  /**
   * Gets the expression of the component.
   *
   * @return \Drupal\rules\Engine\ExpressionInterface
   *   The expression.
   */
  public function getExpression() {
    return $this->expression;
  }

  /**
   * Gets the execution state.
   *
   * @return \Drupal\rules\Engine\ExecutionStateInterface
   *   The execution state for this component.
   */
  public function getState() {
    return $this->state;
  }

  /**
   * Adds a context definition.
   *
   * @param string $name
   *   The name of the context to add.
   * @param \Drupal\rules\Context\ContextDefinitionInterface $definition
   *   The definition to add.
   *
   * @return $this
   */
  public function addContextDefinition($name, ContextDefinitionInterface $definition) {
    $this->contextDefinitions[$name] = $definition;
    return $this;
  }

  /**
   * Gets definitions for the context used by this component.
   *
   * @return \Drupal\rules\Context\ContextDefinitionInterface[]
   *   The array of context definitions, keyed by context name.
   */
  public function getContextDefinitions() {
    return $this->contextDefinitions;
  }

  /**
   * Marks the given context to be provided back to the caller.
   *
   * @param string $name
   *   The name of the context to provide.
   *
   * @return $this
   */
  public function provideContext($name) {
    $this->providedContext[] = $name;
    return $this;
  }

  /**
   * Returns the names of context that is provided back to the caller.
   *
   * @return string[]
   *   The names of the context that is provided back.
   */
  public function getProvidedContext() {
    return $this->providedContext;
  }

  /**
   * Sets the value of a context.
   *
   * @param string $name
   *   The name.
   * @param mixed $value
   *   The context value.
   *
   * @throws \LogicException
   *   Thrown if the passed context is not defined.
   *
   * @return $this
   */
  public function setContextValue($name, $value) {
    if (!isset($this->contextDefinitions[$name])) {
      throw new \LogicException("The specified context '$name' is not defined.");
    }
    $this->state->addVariable($name, $this->contextDefinitions[$name], $value);
    return $this;
  }

  /**
   * Executes the component with the previously set context.
   *
   * @return mixed[]
   *   The array of provided context values, keyed by context name.
   *
   * @throws \Drupal\rules\Exception\RulesEvaluationException
   *   Thrown if the Rules expression triggers errors during execution.
   */
  public function execute() {
    $this->expression->executeWithState($this->state);
    $this->state->autoSave();
    $result = [];
    foreach ($this->providedContext as $name) {
      $result[$name] = $this->state->getVariableValue($name);
    }
    return $result;
  }

  /**
   * Executes the component with the given values.
   *
   * @param mixed[] $arguments
   *   The array of arguments; i.e., an array keyed by name of the defined
   *   context and the context value as argument.
   *
   * @return mixed[]
   *   The array of provided context values, keyed by context name.
   *
   * @throws \LogicException
   *   Thrown if the context is not defined.
   * @throws \Drupal\rules\Exception\RulesEvaluationException
   *   Thrown if the Rules expression triggers errors during execution.
   */
  public function executeWithArguments(array $arguments) {
    $this->state = ExecutionState::create();
    foreach ($arguments as $name => $value) {
      $this->setContextValue($name, $value);
    }
    return $this->execute();
  }

}
