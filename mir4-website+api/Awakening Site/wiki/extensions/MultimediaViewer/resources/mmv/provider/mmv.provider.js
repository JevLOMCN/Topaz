/*
 * This file is part of the MediaWiki extension MultimediaViewer.
 *
 * MultimediaViewer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * MultimediaViewer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MultimediaViewer.  If not, see <http://www.gnu.org/licenses/>.
 */

const Api = require( './mmv.provider.Api.js' );
const FileRepoInfo = require( './mmv.provider.FileRepoInfo.js' );
const GuessedThumbnailInfo = require( './mmv.provider.GuessedThumbnailInfo.js' );
const ImageProvider = require( './mmv.provider.Image.js' );
const ImageInfo = require( './mmv.provider.ImageInfo.js' );
const ThumbnailInfo = require( './mmv.provider.ThumbnailInfo.js' );

module.exports = {
	Api,
	FileRepoInfo,
	GuessedThumbnailInfo,
	ImageInfo,
	ImageProvider,
	ThumbnailInfo
};
