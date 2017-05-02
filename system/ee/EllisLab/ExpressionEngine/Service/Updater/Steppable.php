<?php
/**
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2017, EllisLab, Inc. (https://ellislab.com)
 * @license   https://expressionengine.com/license
 */

namespace EllisLab\ExpressionEngine\Service\Updater;

/**
 * This is a handy queue-like system which allows you to iterate over
 * pre-defined steps to accomplish a goal. You can inject steps at any point
 * and start the iterator from any point.
 */
trait Steppable {

	protected $currentStep;
	protected $nextStep;

	/**
	 * Runs all steps in sequence
	 */
	public function run()
	{
		while (($next_step = $this->getNextStep()) !== FALSE)
		{
			$this->runStep($next_step);
		}
	}

	/**
	 * Runs an individual step
	 */
	public function runStep($step)
	{
		$this->currentStep = $step;
		$this->nextStep = NULL;

		list($step, $parameters) = $this->parseStepString($step);

		$return = call_user_func_array([$this, $step], $parameters);

		// If we got a string back, we assume it's a method name with optional
		// parameters, insert it into the steps array to be called next
		if (is_string($return))
		{
			$index = array_search($this->currentStep, $this->steps);

			if ($index === FALSE OR in_array($return, $this->steps))
			{
				$this->nextStep = $return;
				return;
			}

			if ( ! in_array($return, $this->steps))
			{
				array_splice($this->steps, $index + 1, 0, $return);
			}
		}
	}

	/**
	 * Split up the step method name and its parameters, e.g. 'method[param1,param2]'
	 *
	 * @param	string	$string	Step method
	 * @return	array	[step method, [...parameters]]
	 */
	protected function parseStepString($string)
	{
		if (preg_match("/(.*?)\[(.*?)\]$/", $string, $match))
		{
			$rule_name	= $match[1];
			$parameters	= $match[2];

			$parameters = explode(',', $parameters);
			$parameters = array_map('trim', $parameters);

			return [$rule_name, $parameters];
		}

		return [$string, []];
	}

	/**
	 * Gets the first step
	 *
	 * @return	string	Name of first step
	 */
	public function getFirstStep()
	{
		return $this->steps[0];
	}

	/**
	 * Gets the next step after the one that was most-recently run
	 *
	 * @return	string	Name of next step
	 */
	public function getNextStep()
	{
		if (empty($this->currentStep))
		{
			return $this->getFirstStep();
		}

		if ( ! is_null($this->nextStep))
		{
			return $this->nextStep;
		}

		$index = array_search($this->currentStep, $this->steps);

		if ($index !== FALSE && isset($this->steps[$index+1]))
		{
			return $this->steps[$index+1];
		}

		return FALSE;
	}
}
// EOF
