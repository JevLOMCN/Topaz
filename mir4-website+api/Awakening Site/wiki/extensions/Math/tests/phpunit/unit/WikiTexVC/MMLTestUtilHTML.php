<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util;

/**
 * This class contains functions to generate a
 * HTML File which shows the formula converted to MathML
 * by WikiTexVC
 * @author Johannes Stegmüller
 */
class MMLTestUtilHTML {

	public static function generateHTMLtableItem( $input, $bold = false ) {
		if ( !$bold ) {
			return "<td class=\"tg-0lax\">" . $input . "</td>";
		} else {
			return "<td class=\"tg-0lax\">" . "<b>" . $input . "</b>" . "</td>";
		}
	}

	public static function generateHTMLEnd( $filePath, $active = true ) {
		if ( !$active ) {
			return;
		}
		$file = fopen( $filePath, 'a' );
		fwrite( $file, "</tbody></table>" );
		fclose( $file );
	}

	public static function generateHTMLtableRow( $filePath, $rows, $bold = false, $active = true ) {
		if ( !$active ) {
			return;
		}
		$file = fopen( $filePath, 'a' );

		$stringData = "<tr>";
		foreach ( $rows as $row ) {
			$stringData .= self::generateHTMLtableItem( $row, $bold );
		}
		$stringData .= "</tr>";

		fwrite( $file, $stringData );

		fclose( $file ); // tbd only open close once for all tests
	}

	public static function generateHTMLstart(
		$filePath,
		$headrows = [ "name", "Tex-Input", "MathML(MathJax3)", "MathML(WikiTexVC)" ],
		$active = true
	) {
		if ( !$active ) {
			return;
		}

		$htmlRows = "";
		foreach ( $headrows as $header ) {
			$htmlRows .= "<th class=\"tg-0lax\"><b>" . $header . "</b></th>";
		}

		$file = fopen( $filePath, 'w' ); // or die("error");
		$stringData = /** @lang HTML */
			<<<HTML
			<!DOCTYPE html>
			<html lang="en">
			<head>
				<meta charset="utf-8">
			</head>
			<style>
				.tg {
					border-collapse: collapse;
					border-spacing: 0;
				}
				.tg td {
					border-color: black;
					border-style: solid;
					border-width: 1px;
					font-family: Arial, sans-serif;
					font-size: 14px;
					overflow: hidden;
					padding: 10px 5px;
					word-break: normal;
				}
				.tg th {
					border-color: black;
					border-style: solid;
					border-width: 1px;
					font-family: Arial,
					sans-serif;
					font-size: 14px;
					font-weight: normal;
					overflow: hidden;
					padding: 10px 5px;
					word-break: normal;
				}
				.tg .tg-0lax {
					text-align: left;
					vertical-align: top
				}
			</style>
			<table class="tg">
				<thead>
				<tr>{$htmlRows}</tr>
				</thead>
				<tbody>
			HTML;
		fwrite( $file, $stringData );
		fclose( $file );
	}
}
