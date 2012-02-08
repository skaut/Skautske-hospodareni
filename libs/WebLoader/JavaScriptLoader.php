<?php

/**
 * JavaScript loader
 *
 * @author Jan Marek
 * @license MIT
 */
class JavaScriptLoader extends WebLoader {

	/**
	 * Construct
	 * @param IComponentContainer parent
	 * @param string name
	 */
	public function __construct(IComponentContainer $parent = null, $name = null) {
		parent::__construct($parent, $name);
		$this->setGeneratedFileNamePrefix("jsloader-");
		$this->setGeneratedFileNameSuffix(".js");
	}

	/**
	 * Get script element
	 * @param string $source
	 * @return Html
	 */
	public function getElement($source) {
		return Html::el("script")->type("text/javascript")->src($source);
	}
}