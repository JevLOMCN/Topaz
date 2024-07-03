<?php

namespace MediaWiki\Extension\DiscussionTools;

use JsonSerializable;

/**
 * Item to go into the DiscussionTools overflow menu, as an OO.ui.MenuOptionWidget object.
 */
class OverflowMenuItem implements JsonSerializable {

	private string $icon;
	private string $label;
	private array $data;
	private string $id;
	private int $weight;

	/**
	 * @param string $id A unique identifier for the menu item, e.g. 'edit' or 'reportincident'
	 * @param string $icon An OOUI icon name.
	 * @param string $label A rendered string to use as the label for the item.
	 * @param int $weight Sorting weight. Higher values will push the item further up the menu.
	 * @param array $data Data to include with the menu item. Will be accessible via getData() on the
	 *   OOUI MenuOptionWidget in client-side code.
	 */
	public function __construct(
		string $id,
		string $icon,
		string $label,
		int $weight = 0,
		array $data = []
	) {
		$this->id = $id;
		$this->icon = $icon;
		$this->label = $label;
		$this->weight = $weight;
		$this->data = $data;
	}

	public function jsonSerialize(): array {
		$data = $this->data;
		// Add 'id' into the 'data' array, for easier access with OOUI's getData() method
		$data['id'] = $this->id;
		return [
			'id' => $this->id,
			'data' => $data,
			'icon' => $this->icon,
			'label' => $this->label,
		];
	}

	public function getId(): string {
		return $this->id;
	}

	public function getWeight(): int {
		return $this->weight;
	}
}
