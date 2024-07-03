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

const ImageModel = require( './mmv.model.Image.js' );
const IwTitle = require( './mmv.model.IwTitle.js' );
const License = require( './mmv.model.License.js' );
const { Repo, ForeignApiRepo, ForeignDbRepo } = require( './mmv.model.Repo.js' );
const TaskQueue = require( './mmv.model.TaskQueue.js' );
const Thumbnail = require( './mmv.model.Thumbnail.js' );
const ThumbnailWidth = require( './mmv.model.ThumbnailWidth.js' );

module.exports = {
	ImageModel,
	IwTitle,
	License,
	Repo,
	ForeignApiRepo,
	ForeignDbRepo,
	TaskQueue,
	Thumbnail,
	ThumbnailWidth
};
