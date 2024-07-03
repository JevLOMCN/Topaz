<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @ingroup Maintenance
 */

use MediaWiki\Extension\Math\MathRenderer;
use MediaWiki\MediaWikiServices;

require_once __DIR__ . '/../../../maintenance/Maintenance.php';

/**
 * From a specified json file with (La)TeX formula as input,
 * create a json file with the Tex and corresponding MathML.
 * This is mostly used for generating Test-Content for the MathML features of WikiTexVC.
 *
 * The script fetches:
 * - Mathoid MathML (mode: 'mathml')
 * - LaTeXML MathML (mode: 'latexml')
 *
 * @author Johannes Stegmüller
 */
class JsonToMathML extends Maintenance {
	/** @var string */
	private $inputPath = "";

	/** @var string */
	private $outputPath = "";
	/** @var int */
	private $inputFormat = 0;

	public function __construct() {
		parent::__construct();
		$this->addDescription( 'From a JSON file containing (La)TeX math inputs, create a JSON file with LaTeX and ' .
			'corresponding MathML.' );
		$this->addArg( 'input-path', "Path (with filename) of the json file read by this script.",
			true );
		$this->addArg( 'output-path', "Path (with filename) of the output json file created by this script.",
			true );
		$this->addOption( 'inputformat', 'Custom parsing how to format the input-json ( see formatInput function)',
			false, true, 'i' );
		$this->addOption( 'chem-fallback', 'If the json read does not define input-type (tex or chem), check ' .
			'expressions as Tex and then as chem', false, true, 'c' );
		$this->requireExtension( 'Math' );
	}

	public function execute() {
		$this->inputFormat = $this->getOption( "inputformat", 0 );
		$this->inputPath = $this->getArg( 0 );
		$this->outputPath = $this->getArg( 1 );
		$this->readJsonAndGenerateMML();
	}

	public function readJsonAndGenerateMML() {
		$inputTex = $this->getJSON( $this->inputPath );
		if ( $inputTex == null ) {
			throw new InvalidArgumentException( "Provide a json file as input which has content" );
		}
		$inputTexF = $this->formatInput( $inputTex );
		$allEntries = [];

		foreach ( $inputTexF as $entry ) {
			try {
				$mmlMathoid = $this->fetchMathML( $entry['tex'], $entry['type'], 'mathml' );
				if ( ( $this->getOption( "chem-fallback", 0 ) && !$mmlMathoid ) || $mmlMathoid == "" ) {
					$mmlMathoid = $this->fetchMathML( $entry['tex'], "chem", 'mathml' );
					if ( $mmlMathoid && $mmlMathoid != "" ) {
						$entry['type'] = "chem";
					}
				}
				$mmlLaTeXML = $this->fetchMathML( $entry['tex'], $entry['type'], 'latexml' );
				$entryToAdd = [
					"tex" => $entry['tex'],
					"type" => $entry['type'],
					"mmlMathoid" => $mmlMathoid,
					"mmlLaTeXML" => $mmlLaTeXML
				];

				if ( array_key_exists( "texNew", $entry ) ) {
					$entryToAdd["texNew"] = $entry["texNew"];
				}
				if ( array_key_exists( "typeC", $entry ) ) {
					$entryToAdd["typeC"] = $entry["typeC"];
				}
				if ( array_key_exists( "description", $entry ) ) {
					$entryToAdd["description"] = $entry["description"];
				}

				$allEntries[] = $entryToAdd;
			} catch ( Exception $e ) {
				$allEntries[] = [
					"tex" => $entry['tex'],
					"type" => $entry['type'],
					"mmlMathoid" => "skipped (Exception)",
					"mmlLaTeXML" => "skipped (Exception)",
					"skipped" => true,
				];
				$this->output( "Exception occurred during rendering:" . $entry['tex'] . " render:" . $e . "\n" );
			}
		}

		$this->writeToFile( $this->outputPath, $allEntries );
	}

	/**
	 * Creates a uniform array of data from files for more convenient parsing.
	 * @param array $fileData input read from json, format can differ
	 * @return array uniform array
	 */
	private function formatInput( $fileData ) {
		$inputF = [];
		switch ( $this->inputFormat ) {
			case 0:
				// Example file ParserTest135.json
				foreach ( $fileData as $entry ) {
					$inputF[] = [
						"tex" => $entry['input'],
						"type" => "tex",
					];
				}
				break;
			case 1:
				// Example file TexUtilMMLLookup.json
				foreach ( $fileData as $tex => $mml ) {
					$inputF[] = [
						"tex" => $tex,
						"type" => "tex",
					];
				}
				break;
			case 2:
				// Example file ExportedTexUtilKeys.json
				foreach ( $fileData as $tex => $type ) {
					$inputF[] = [
						"tex" => $tex,
						"type" => $type,
					];
				}
				break;
			case 3:
				// Example file Mhchemv4tex.json
				foreach ( $fileData as $group => $cases ) {
					foreach ( $cases as $case ) {
						$inputF[] = [
							"description" => $group,
							"tex" => $case["tex"],
							"texNew" => $case["texNew"],
							"type" => "chem",
							"typeC" => $case["type"]
						];
					}
				}
				break;
			case 4:
				// Example file ExamplesNewCommandsMhchem.json
				foreach ( $fileData as $entry ) {
					$inputF[] = [
						"description" => $entry["description"],
						"tex" => $entry['tex'],
						"type" => $entry['type'],
					];
				}
				break;
		}
		return $inputF;
	}

	/**
	 * Reads the json file to an object
	 * @param string $filePath filepath to the json-file
	 * @return array
	 */
	private function getJSON( string $filePath ) {
		$file = file_get_contents( $filePath );
		$json = json_decode( $file, true );
		return $json;
	}

	public function writeToFile( string $fullPath, array $allEntries ): void {
		$jsonData = json_encode( $allEntries, JSON_PRETTY_PRINT );
		file_put_contents( $fullPath, $jsonData );
	}

	/**
	 * Creates a renderer and fetches the generated MathML
	 * @param string $tex input tex
	 * @param string $type input type ('tex' or 'chem')
	 * @param string $renderingMode mode for rendering (latexml, mathml)
	 * @return string MathML as string
	 */
	public function fetchMathML( string $tex, string $type, string $renderingMode ): string {
		$params = [ "type" => $type ];
		if ( $type == "chem" ) {
			$params["chem"] = true;
			$tex = "\\ce{ " . $tex . " }";
		}
		/** @var MathRenderer $renderer */
		$renderer = MediaWikiServices::getInstance()->get( 'Math.RendererFactory' )
			->getRenderer( $tex, $params, $renderingMode );
		$renderer->render();
		$mml = $renderer->getMathml();
		return $mml;
	}
}

$maintClass = JsonToMathML::class;
require_once RUN_MAINTENANCE_IF_MAIN;
