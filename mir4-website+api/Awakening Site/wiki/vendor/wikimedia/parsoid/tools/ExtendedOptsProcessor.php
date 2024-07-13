<?php

namespace Wikimedia\Parsoid\Tools;

trait ExtendedOptsProcessor {
	private $optionDefaults = [];

	/**
	 * @param string $name
	 * @param mixed $default
	 */
	public function setOptionDefault( string $name, $default ) {
		$this->optionDefaults[$name] = $default;
	}

	/**
	 * Add a parameter to the script with a default value.
	 * Will be displayed on --help with the associated description.
	 *
	 * @param string $name The name of the param (help, version, etc)
	 * @param string $description The description of the param to show on --help
	 * @param mixed $defaultValue Default value (default null)
	 * @param string|bool $shortName Character to use as short name
	 * @param bool $required Is the param required?
	 */
	public function addOptionWithDefault(
		string $name, string $description,
		$defaultValue = null,
		$shortName = false,
		bool $required = false
	) {
		$this->addOption(
			$name,
			"$description (default: $defaultValue)",
			$required, true, /* withArg */
			$shortName, false /* multiOccurence */
		);
		$this->setOptionDefault(
			$name, $defaultValue
		);
	}

	/** @inheritDoc */
	public function getOption( $name, $default = null ) {
		return parent::getOption(
			$name, $default ?? $this->optionDefaults[$name] ?? null
		);
	}

	/**
	 * Return all known CLI options in an associative array
	 * @return array
	 */
	public function optionsToArray(): array {
		return $this->getOptions() + $this->optionDefaults;
	}
}
