<?php
/**
 * Definitions for Reader class.
 *
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
 * @file
 * @ingroup Media
 */

namespace Wikimedia\XMPReader;

/**
 * This class is just a container for a big array
 * used by Reader to determine which XMP items to
 * extract.
 *
 * @codeCoverageIgnore
 */
class Info {
	/**
	 * Get the items array
	 * @return array XMP item configuration array.
	 */
	public static function getItems(): array {
		return self::$items;
	}

	/**
	 * XMPInfo::$items keeps a list of all the items
	 * we are interested to extract, as well as
	 * information about the item like what type
	 * it is.
	 *
	 * Format is an array of namespaces,
	 * each containing an array of tags
	 * each tag is an array of information about the
	 * tag, including:
	 *   * map_group - What group (used for precedence during conflicts in
	 *     accordance with http://www.metadataworkinggroup.org/pdf/mwg_guidance.pdf )
	 *   * mode - What type of item (self::MODE_SIMPLE usually, see above for
	 *     all values).
	 *   * validate - Method to validate input. Could also post-process the
	 *     input. A string value is assumed to be a method of
	 *     XMPValidate. Can also take a array( 'className', 'methodName' ).
	 *   * choices - Array of potential values (format of 'value' => true ).
	 *     Only used with validateClosed.
	 *   * rangeLow and rangeHigh - Alternative to choices for numeric ranges.
	 *     Again for validateClosed only.
	 *   * children - For MODE_STRUCT items, allowed children.
	 *   * structPart - Indicates that this element can only appear as a member
	 *     of a structure.
	 *
	 * Currently, this just has a bunch of EXIF values as this class is only half-done.
	 * @var array
	 */
	private static array $items = [
		'http://ns.adobe.com/exif/1.0/' => [
			'ApertureValue' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateRational'
			],
			'BrightnessValue' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateRational'
			],
			'CompressedBitsPerPixel' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateRational'
			],
			'DigitalZoomRatio' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateRational'
			],
			'ExposureBiasValue' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateRational'
			],
			'ExposureIndex' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateRational'
			],
			'ExposureTime' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateRational'
			],
			'FlashEnergy' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateRational',
			],
			'FNumber' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateRational'
			],
			'FocalLength' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateRational'
			],
			'FocalPlaneXResolution' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateRational'
			],
			'FocalPlaneYResolution' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateRational'
			],
			'GPSAltitude' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateRational',
			],
			'GPSDestBearing' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateRational'
			],
			'GPSDestDistance' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateRational'
			],
			'GPSDOP' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateRational'
			],
			'GPSImgDirection' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateRational'
			],
			'GPSSpeed' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateRational'
			],
			'GPSTrack' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateRational'
			],
			'MaxApertureValue' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateRational'
			],
			'ShutterSpeedValue' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateRational'
			],
			'SubjectDistance' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateRational'
			],
			/* Flash */
			'Flash' => [
				'mode' => Reader::MODE_STRUCT,
				'children' => [
					'Fired' => true,
					'Function' => true,
					'Mode' => true,
					'RedEyeMode' => true,
					'Return' => true,
				],
				'validate' => 'validateFlash',
				'map_group' => 'exif',
			],
			'Fired' => [
				'map_group' => 'exif',
				'validate' => 'validateBoolean',
				'mode' => Reader::MODE_SIMPLE,
				'structPart' => true,
			],
			'Function' => [
				'map_group' => 'exif',
				'validate' => 'validateBoolean',
				'mode' => Reader::MODE_SIMPLE,
				'structPart' => true,
			],
			'Mode' => [
				'map_group' => 'exif',
				'validate' => 'validateClosed',
				'mode' => Reader::MODE_SIMPLE,
				'choices' => [ '0' => true, '1' => true,
					'2' => true, '3' => true ],
				'structPart' => true,
			],
			'Return' => [
				'map_group' => 'exif',
				'validate' => 'validateClosed',
				'mode' => Reader::MODE_SIMPLE,
				'choices' => [ '0' => true,
					'2' => true, '3' => true ],
				'structPart' => true,
			],
			'RedEyeMode' => [
				'map_group' => 'exif',
				'validate' => 'validateBoolean',
				'mode' => Reader::MODE_SIMPLE,
				'structPart' => true,
			],
			/* End Flash */
			'ISOSpeedRatings' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SEQ,
				'validate' => 'validateInteger'
			],
			/* end rational things */
			'ColorSpace' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'choices' => [ '1' => true, '65535' => true ],
			],
			'ComponentsConfiguration' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SEQ,
				'validate' => 'validateClosed',
				'choices' => [ '1' => true, '2' => true, '3' => true, '4' => true,
					'5' => true, '6' => true ]
			],
			'Contrast' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'choices' => [ '0' => true, '1' => true, '2' => true ]
			],
			'CustomRendered' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'choices' => [ '0' => true, '1' => true ]
			],
			'DateTimeOriginal' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateDate',
			],
			'DateTimeDigitized' => [
				/* xmp:CreateDate */
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateDate',
			],
			/* todo: there might be interesting information in
			 * exif:DeviceSettingDescription, but need to find an
			 * example
			 */
			'ExifVersion' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
			],
			'ExposureMode' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'rangeLow' => 0,
				'rangeHigh' => 2,
			],
			'ExposureProgram' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'rangeLow' => 0,
				'rangeHigh' => 8,
			],
			'FileSource' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'choices' => [ '3' => true ]
			],
			// PHP likes to be the odd one out with casing of FlashPixVersion;
			// https://www.exif.org/Exif2-2.PDF#page=32 and
			// https://www.digitalgalen.net/Documents/External/XMP/XMPSpecificationPart2.pdf#page=51
			// both use FlashpixVersion. However, since at least 2002, PHP has used FlashPixVersion at
			// https://github.com/php/php-src/blame/master/ext/exif/exif.c#L725
			'FlashpixVersion' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
			],
			'FocalLengthIn35mmFilm' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateInteger',
			],
			'FocalPlaneResolutionUnit' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'choices' => [ '2' => true, '3' => true ],
			],
			'GainControl' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'rangeLow' => 0,
				'rangeHigh' => 4,
			],
			/* this value is post-processed out later */
			'GPSAltitudeRef' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'choices' => [ '0' => true, '1' => true ],
			],
			'GPSAreaInformation' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
			],
			'GPSDestBearingRef' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'choices' => [ 'T' => true, 'M' => true ],
			],
			'GPSDestDistanceRef' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'choices' => [ 'K' => true, 'M' => true,
					'N' => true ],
			],
			'GPSDestLatitude' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateGPS',
			],
			'GPSDestLongitude' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateGPS',
			],
			'GPSDifferential' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'choices' => [ '0' => true, '1' => true ],
			],
			'GPSImgDirectionRef' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'choices' => [ 'T' => true, 'M' => true ],
			],
			'GPSLatitude' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateGPS',
			],
			'GPSLongitude' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateGPS',
			],
			'GPSMapDatum' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
			],
			'GPSMeasureMode' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'choices' => [ '2' => true, '3' => true ]
			],
			'GPSProcessingMethod' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
			],
			'GPSSatellites' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
			],
			'GPSSpeedRef' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'choices' => [ 'K' => true, 'M' => true,
					'N' => true ],
			],
			'GPSStatus' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'choices' => [ 'A' => true, 'V' => true ]
			],
			'GPSTimeStamp' => [
				'map_group' => 'exif',
				// Note: in exif, GPSDateStamp does not include
				// the time, where here it does.
				'map_name' => 'GPSDateStamp',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateDate',
			],
			'GPSTrackRef' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'choices' => [ 'T' => true, 'M' => true ]
			],
			'GPSVersionID' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
			],
			'ImageUniqueID' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
			],
			'LightSource' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				/* can't use a range, as it skips... */
				'choices' => [ '0' => true, '1' => true,
					'2' => true, '3' => true, '4' => true,
					'9' => true, '10' => true, '11' => true,
					'12' => true, '13' => true,
					'14' => true, '15' => true,
					'17' => true, '18' => true,
					'19' => true, '20' => true,
					'21' => true, '22' => true,
					'23' => true, '24' => true,
					'255' => true,
				],
			],
			'MeteringMode' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'rangeLow' => 0,
				'rangeHigh' => 6,
				'choices' => [ '255' => true ],
			],
			/* Pixel(X|Y)Dimension are rather useless, but for
			 * completeness since we do it with exif.
			 */
			'PixelXDimension' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateInteger',
			],
			'PixelYDimension' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateInteger',
			],
			'Saturation' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'rangeLow' => 0,
				'rangeHigh' => 2,
			],
			'SceneCaptureType' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'rangeLow' => 0,
				'rangeHigh' => 3,
			],
			'SceneType' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'choices' => [ '1' => true ],
			],
			// Note, 6 is not valid SensingMethod.
			'SensingMethod' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'rangeLow' => 1,
				'rangeHigh' => 5,
				'choices' => [ '7' => true, 8 => true ],
			],
			'Sharpness' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'rangeLow' => 0,
				'rangeHigh' => 2,
			],
			'SpectralSensitivity' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
			],
			// This tag should perhaps be displayed to user better.
			'SubjectArea' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SEQ,
				'validate' => 'validateInteger',
			],
			'SubjectDistanceRange' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'rangeLow' => 0,
				'rangeHigh' => 3,
			],
			'SubjectLocation' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SEQ,
				'validate' => 'validateInteger',
			],
			'UserComment' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_LANG,
			],
			'WhiteBalance' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'choices' => [ '0' => true, '1' => true ]
			],
		],
		'http://ns.adobe.com/tiff/1.0/' => [
			'Artist' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
			],
			'BitsPerSample' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SEQ,
				'validate' => 'validateInteger',
			],
			'Compression' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'choices' => [ '1' => true, '6' => true ],
			],
			/* this prop should not be used in XMP. dc:rights is the correct prop */
			'Copyright' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_LANG,
			],
			'DateTime' => [
				/* proper prop is xmp:ModifyDate */
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateDate',
			],
			'ImageDescription' => [
				/* proper one is dc:description */
				'map_group' => 'exif',
				'mode' => Reader::MODE_LANG,
			],
			'ImageLength' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateInteger',
			],
			'ImageWidth' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateInteger',
			],
			'Make' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
			],
			'Model' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
			],
			/** Do not extract this property
			 * It interferes with auto exif rotation.
			 * 'Orientation'       => array(
			 *    'map_group' => 'exif',
			 *    'mode'      => Reader::MODE_SIMPLE,
			 *    'validate'  => 'validateClosed',
			 *    'choices'   => array( '1' => true, '2' => true, '3' => true, '4' => true, 5 => true,
			 *            '6' => true, '7' => true, '8' => true ),
			 * ),
			 */
			'PhotometricInterpretation' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'choices' => [ '2' => true, '6' => true ],
			],
			'PlanerConfiguration' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'choices' => [ '1' => true, '2' => true ],
			],
			'PrimaryChromaticities' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SEQ,
				'validate' => 'validateRational',
			],
			'ReferenceBlackWhite' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SEQ,
				'validate' => 'validateRational',
			],
			'ResolutionUnit' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'choices' => [ '2' => true, '3' => true ],
			],
			'SamplesPerPixel' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateInteger',
			],
			'Software' => [
				/* see xmp:CreatorTool */
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
			],
			/* ignore TransferFunction */
			'WhitePoint' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SEQ,
				'validate' => 'validateRational',
			],
			'XResolution' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateRational',
			],
			'YResolution' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateRational',
			],
			'YCbCrCoefficients' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SEQ,
				'validate' => 'validateRational',
			],
			'YCbCrPositioning' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'choices' => [ '1' => true, '2' => true ],
			],
			/**
			 * Disable extracting this property (T33944)
			 * Several files have a string instead of a Seq
			 * for this property. Reader doesn't handle
			 * mismatched types very gracefully (it marks
			 * the entire file as invalid, instead of just
			 * the relavent prop). Since this prop
			 * doesn't communicate all that useful information
			 * just disable this prop for now, until such
			 * Reader is more graceful (T34172)
			 * 'YCbCrSubSampling'  => array(
			 *    'map_group' => 'exif',
			 *    'mode'      => Reader::MODE_SEQ,
			 *    'validate'  => 'validateClosed',
			 *    'choices'   => array( '1' => true, '2' => true ),
			 * ),
			 */
		],
		'http://ns.adobe.com/exif/1.0/aux/' => [
			'Lens' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
			],
			'SerialNumber' => [
				'map_group' => 'exif',
				'mode' => Reader::MODE_SIMPLE,
			],
			'OwnerName' => [
				'map_group' => 'exif',
				'map_name' => 'CameraOwnerName',
				'mode' => Reader::MODE_SIMPLE,
			],
		],
		'http://purl.org/dc/elements/1.1/' => [
			'title' => [
				'map_group' => 'general',
				'map_name' => 'ObjectName',
				'mode' => Reader::MODE_LANG
			],
			'description' => [
				'map_group' => 'general',
				'map_name' => 'ImageDescription',
				'mode' => Reader::MODE_LANG
			],
			'contributor' => [
				'map_group' => 'general',
				'map_name' => 'dc-contributor',
				'mode' => Reader::MODE_BAG
			],
			'coverage' => [
				'map_group' => 'general',
				'map_name' => 'dc-coverage',
				'mode' => Reader::MODE_SIMPLE,
			],
			'creator' => [
				'map_group' => 'general',
				// map with exif Artist, iptc byline (2:80)
				'map_name' => 'Artist',
				'mode' => Reader::MODE_SEQ,
			],
			'date' => [
				'map_group' => 'general',
				// Note, not mapped with other date properties, as this type of date is
				// non-specific: "A point or period of time associated with an event in
				// the lifecycle of the resource"
				'map_name' => 'dc-date',
				'mode' => Reader::MODE_SEQ,
				'validate' => 'validateDate',
			],
			/* Do not extract dc:format, as we've got better ways to determine MIME type */
			'identifier' => [
				'map_group' => 'deprecated',
				'map_name' => 'Identifier',
				'mode' => Reader::MODE_SIMPLE,
			],
			'language' => [
				'map_group' => 'general',
				/* mapped with iptc 2:135 */
				'map_name' => 'LanguageCode',
				'mode' => Reader::MODE_BAG,
				'validate' => 'validateLangCode',
			],
			'publisher' => [
				'map_group' => 'general',
				'map_name' => 'dc-publisher',
				'mode' => Reader::MODE_BAG,
			],
			// for related images/resources
			'relation' => [
				'map_group' => 'general',
				'map_name' => 'dc-relation',
				'mode' => Reader::MODE_BAG,
			],
			'rights' => [
				'map_group' => 'general',
				'map_name' => 'Copyright',
				'mode' => Reader::MODE_LANG,
			],
			// Note: source is not mapped with iptc source, since iptc
			// source describes the source of the image in terms of a person
			// who provided the image, where this is to describe an image that the
			// current one is based on.
			'source' => [
				'map_group' => 'general',
				'map_name' => 'dc-source',
				'mode' => Reader::MODE_SIMPLE,
			],
			'subject' => [
				'map_group' => 'general',
				/* maps to iptc 2:25 */
				'map_name' => 'Keywords',
				'mode' => Reader::MODE_BAG,
			],
			'type' => [
				'map_group' => 'general',
				'map_name' => 'dc-type',
				'mode' => Reader::MODE_BAG,
			],
		],
		'http://ns.adobe.com/xap/1.0/' => [
			'CreateDate' => [
				'map_group' => 'general',
				'map_name' => 'DateTimeDigitized',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateDate',
			],
			'CreatorTool' => [
				'map_group' => 'general',
				'map_name' => 'Software',
				'mode' => Reader::MODE_SIMPLE
			],
			'Identifier' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_BAG,
			],
			'Label' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
			],
			'ModifyDate' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'map_name' => 'DateTime',
				'validate' => 'validateDate',
			],
			'MetadataDate' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				// map_name to be consistent with other date names.
				'map_name' => 'DateTimeMetadata',
				'validate' => 'validateDate',
			],
			'Nickname' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
			],
			'Rating' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateRating',
			],
		],
		'http://ns.adobe.com/xap/1.0/rights/' => [
			'Certificate' => [
				'map_group' => 'general',
				'map_name' => 'RightsCertificate',
				'mode' => Reader::MODE_SIMPLE,
			],
			'Marked' => [
				'map_group' => 'general',
				'map_name' => 'Copyrighted',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateBoolean',
			],
			'Owner' => [
				'map_group' => 'general',
				'map_name' => 'CopyrightOwner',
				'mode' => Reader::MODE_BAG,
			],
			// this seems similar to dc:rights.
			'UsageTerms' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_LANG,
			],
			'WebStatement' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
			],
		],
		// XMP media management.
		'http://ns.adobe.com/xap/1.0/mm/' => [
			// if we extract the exif UniqueImageID, might
			// as well do this too.
			'OriginalDocumentID' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
			],
			// It might also be useful to do xmpMM:LastURL
			// and xmpMM:DerivedFrom as you can potentially,
			// get the url of this document/source for this
			// document. However whats more likely is you'd
			// get a file:// url for the path of the doc,
			// which is somewhat of a privacy issue.
		],
		'http://creativecommons.org/ns#' => [
			'license' => [
				'map_name' => 'LicenseUrl',
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
			],
			'morePermissions' => [
				'map_name' => 'MorePermissionsUrl',
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
			],
			'attributionURL' => [
				'map_group' => 'general',
				'map_name' => 'AttributionUrl',
				'mode' => Reader::MODE_SIMPLE,
			],
			'attributionName' => [
				'map_group' => 'general',
				'map_name' => 'PreferredAttributionName',
				'mode' => Reader::MODE_SIMPLE,
			],
		],
		// Note, this property affects how jpeg metadata is extracted.
		'http://ns.adobe.com/xmp/note/' => [
			'HasExtendedXMP' => [
				'map_group' => 'special',
				'mode' => Reader::MODE_SIMPLE,
			],
		],
		/* Note, in iptc schemas, the legacy properties are denoted
		 * as deprecated, since other properties should used instead,
		 * and properties marked as deprecated in the standard are
		 * are marked as general here as they don't have replacements
		 */
		'http://ns.adobe.com/photoshop/1.0/' => [
			'City' => [
				'map_group' => 'deprecated',
				'mode' => Reader::MODE_SIMPLE,
				'map_name' => 'CityDest',
			],
			'Country' => [
				'map_group' => 'deprecated',
				'mode' => Reader::MODE_SIMPLE,
				'map_name' => 'CountryDest',
			],
			'State' => [
				'map_group' => 'deprecated',
				'mode' => Reader::MODE_SIMPLE,
				'map_name' => 'ProvinceOrStateDest',
			],
			'DateCreated' => [
				'map_group' => 'deprecated',
				// marking as deprecated as the xmp prop preferred
				'mode' => Reader::MODE_SIMPLE,
				'map_name' => 'DateTimeOriginal',
				'validate' => 'validateDate',
				// note this prop is an XMP, not IPTC date
			],
			'CaptionWriter' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'map_name' => 'Writer',
			],
			'Instructions' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'map_name' => 'SpecialInstructions',
			],
			'TransmissionReference' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'map_name' => 'OriginalTransmissionRef',
			],
			'AuthorsPosition' => [
				/* This corresponds with 2:85
				 * By-line Title, which needs to be
				 * handled weirdly to correspond
				 * with iptc/exif. */
				'map_group' => 'special',
				'mode' => Reader::MODE_SIMPLE
			],
			'Credit' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
			],
			'Source' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
			],
			'Urgency' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
			],
			'Category' => [
				// Note, this prop is deprecated, but in general
				// group since it doesn't have a replacement.
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'map_name' => 'iimCategory',
			],
			'SupplementalCategories' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_BAG,
				'map_name' => 'iimSupplementalCategory',
			],
			'Headline' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE
			],
		],
		'http://iptc.org/std/Iptc4xmpCore/1.0/xmlns/' => [
			'CountryCode' => [
				'map_group' => 'deprecated',
				'mode' => Reader::MODE_SIMPLE,
				'map_name' => 'CountryCodeDest',
			],
			'IntellectualGenre' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
			],
			// Note, this is a six digit code.
			// See: http://cv.iptc.org/newscodes/scene/
			// Since these aren't really all that common,
			// we just show the number.
			'Scene' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_BAG,
				'validate' => 'validateInteger',
				'map_name' => 'SceneCode',
			],
			/* Note: SubjectCode should be an 8 ascii digits.
			 * it is not really an integer (has leading 0's,
			 * cannot have a +/- sign), but validateInteger
			 * will let it through.
			 */
			'SubjectCode' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_BAG,
				'map_name' => 'SubjectNewsCode',
				'validate' => 'validateInteger'
			],
			'Location' => [
				'map_group' => 'deprecated',
				'mode' => Reader::MODE_SIMPLE,
				'map_name' => 'SublocationDest',
			],
			'CreatorContactInfo' => [
				/* Note this maps to 2:118 in iim
				 * (Contact) field. However those field
				 * types are slightly different - 2:118
				 * is free form text field, where this
				 * is more structured.
				 */
				'map_group' => 'general',
				'mode' => Reader::MODE_STRUCT,
				'map_name' => 'Contact',
				'children' => [
					'CiAdrExtadr' => true,
					'CiAdrCity' => true,
					'CiAdrCtry' => true,
					'CiEmailWork' => true,
					'CiTelWork' => true,
					'CiAdrPcode' => true,
					'CiAdrRegion' => true,
					'CiUrlWork' => true,
				],
			],
			'CiAdrExtadr' => [
				/* address */
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'structPart' => true,
			],
			'CiAdrCity' => [
				/* city */
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'structPart' => true,
			],
			'CiAdrCtry' => [
				/* country */
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'structPart' => true,
			],
			'CiEmailWork' => [
				/* email (possibly separated by ',') */
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'structPart' => true,
			],
			'CiTelWork' => [
				/* telephone */
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'structPart' => true,
			],
			'CiAdrPcode' => [
				/* postal code */
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'structPart' => true,
			],
			'CiAdrRegion' => [
				/* province/state */
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'structPart' => true,
			],
			'CiUrlWork' => [
				/* url. Multiple may be separated by comma. */
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'structPart' => true,
			],
			/* End contact info struct properties */
		],
		'http://iptc.org/std/Iptc4xmpExt/2008-02-29/' => [
			'Event' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
			],
			'OrganisationInImageName' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_BAG,
				'map_name' => 'OrganisationInImage'
			],
			'PersonInImage' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_BAG,
			],
			'MaxAvailHeight' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateInteger',
				'map_name' => 'OriginalImageHeight',
			],
			'MaxAvailWidth' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateInteger',
				'map_name' => 'OriginalImageWidth',
			],
			// LocationShown and LocationCreated are handled
			// specially because they are hierarchical, but we
			// also want to merge with the old non-hierarchical.
			'LocationShown' => [
				'map_group' => 'special',
				'mode' => Reader::MODE_BAGSTRUCT,
				'children' => [
					'WorldRegion' => true,
					/* iso code */
					'CountryCode' => true,
					'CountryName' => true,
					'ProvinceState' => true,
					'City' => true,
					'Sublocation' => true,
				],
			],
			'LocationCreated' => [
				'map_group' => 'special',
				'mode' => Reader::MODE_BAGSTRUCT,
				'children' => [
					'WorldRegion' => true,
					/* iso code */
					'CountryCode' => true,
					'CountryName' => true,
					'ProvinceState' => true,
					'City' => true,
					'Sublocation' => true,
				],
			],
			'WorldRegion' => [
				'map_group' => 'special',
				'mode' => Reader::MODE_SIMPLE,
				'structPart' => true,
			],
			'CountryCode' => [
				'map_group' => 'special',
				'mode' => Reader::MODE_SIMPLE,
				'structPart' => true,
			],
			'CountryName' => [
				'map_group' => 'special',
				'mode' => Reader::MODE_SIMPLE,
				'structPart' => true,
				'map_name' => 'Country',
			],
			'ProvinceState' => [
				'map_group' => 'special',
				'mode' => Reader::MODE_SIMPLE,
				'structPart' => true,
				'map_name' => 'ProvinceOrState',
			],
			'City' => [
				'map_group' => 'special',
				'mode' => Reader::MODE_SIMPLE,
				'structPart' => true,
			],
			'Sublocation' => [
				'map_group' => 'special',
				'mode' => Reader::MODE_SIMPLE,
				'structPart' => true,
			],

			/* Other props that might be interesting but
			 * Not currently extracted:
			 * ArtworkOrObject, (info about objects in picture)
			 * DigitalSourceType
			 * RegistryId
			 */
		],
		'http://ns.google.com/photos/1.0/panorama/' => [
			// https://developers.google.com/streetview/spherical-metadata
			'UsePanoramaViewer' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateBoolean',
			],
			'CaptureSoftware' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
			],
			'StitchingSoftware' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
			],
			'ProjectionType' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateClosed',
				'choices' => [
					'equirectangular' => true,
				]
			],
			'PoseHeadingDegrees' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateReal',
				'rangeLow' => 0,
				'rangeHigh' => 360,
			],
			'PosePitchDegrees' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateReal',
				'rangeLow' => -90,
				'rangeHigh' => 90,
			],
			'PoseRollDegrees' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateReal',
				'rangeLow' => -180,
				'rangeHigh' => 180,
			],
			'InitialViewHeadingDegrees' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateInteger',
			],
			'InitialViewRollDegrees' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateInteger',
			],
			'InitialHorizontalFOVDegrees' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateReal',
				'rangeLow' => 0,
				'rangeHigh' => 360,
			],
			'InitialVerticalFOVDegrees' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateReal',
				'rangeLow' => 0,
				'rangeHigh' => 360,
			],
			'FirstPhotoDate' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateDate',
			],
			'LastPhotoDate' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateDate',
			],
			'SourcePhotosCount' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateInteger',
			],
			'ExposureLockUsed' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateBoolean',
			],
			'CroppedAreaImageWidthPixels' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateInteger',
			],
			'CroppedAreaImageHeightPixels' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateInteger',
			],
			'FullPanoWidthPixels' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateInteger',
			],
			'FullPanoHeightPixels' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateInteger',
			],
			'CroppedAreaLeftPixels' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateInteger',
			],
			'CroppedAreaTopPixels' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateInteger',
			],
			'InitialCameraDolly' => [
				'map_group' => 'general',
				'mode' => Reader::MODE_SIMPLE,
				'validate' => 'validateReal',
				'rangeLow' => -1,
				'rangeHigh' => 1,
			],
		]

		/* Plus props we might want to consider:
		 * (Note: some of these have unclear/incomplete definitions
		 * from the iptc4xmp standard).
		 * ImageSupplier (kind of like iptc source field)
		 * ImageSupplierId (id code for image from supplier)
		 * CopyrightOwner
		 * ImageCreator
		 * Licensor
		 * Various model release fields
		 * Property release fields.
		 */
	];
}
