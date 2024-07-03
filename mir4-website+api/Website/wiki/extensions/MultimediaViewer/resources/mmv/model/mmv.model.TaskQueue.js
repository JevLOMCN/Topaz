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

( function () {

	/**
	 * A queue which holds a list of tasks (functions). The tasks will be executed in order,
	 * each starting when the previous one has finished (or failed).
	 */
	class TaskQueue {
		constructor() {
			/**
			 * The list of functions to execute.
			 *
			 * @protected
			 * @property {Array}
			 */
			this.queue = [];

			/**
			 * State of the task queue (running, finished etc)
			 *
			 * @protected
			 * @property {TaskQueue.State}
			 */
			this.state = TaskQueue.State.NOT_STARTED;

			/**
			 * A deferred which shows the state of the queue.
			 *
			 * @protected
			 * @property {jQuery.Deferred}
			 */
			this.deferred = $.Deferred();
		}

		/**
		 * Adds a task. The task should be a function which returns a promise. (Other return values are
		 * permitted, and will be taken to mean that the task has finished already.) The next task will
		 * start when the promise resolves (or rejects).
		 *
		 * Tasks can only be added before the queue is first executed.
		 *
		 * @param {function(): any} task
		 */
		push( task ) {
			if ( this.state !== TaskQueue.State.NOT_STARTED ) {
				throw new Error( 'Task queue already started!' );
			}
			this.queue.push( task );
		}

		/**
		 * Execute the queue. The tasks will be performed in order. No more tasks can be added to the
		 * queue.
		 *
		 * @return {jQuery.Promise} a promise which will resolve when the queue execution is finished,
		 *     or reject when it is cancelled.
		 */
		execute() {
			if ( this.state === TaskQueue.State.NOT_STARTED ) {
				this.state = TaskQueue.State.RUNNING;
				this.runNextTask( 0, $.Deferred().resolve() );
			}

			return this.deferred;
		}

		/**
		 * Runs the next task once the current one has finished.
		 *
		 * @param {number} index
		 * @param {jQuery.Promise} currentTask
		 */
		runNextTask( index, currentTask ) {
			const taskQueue = this;

			function handleThen() {
				if ( !taskQueue.queue[ index ] ) {
					taskQueue.state = TaskQueue.State.FINISHED;
					taskQueue.queue = []; // just to be sure there are no memory leaks
					taskQueue.deferred.resolve();
					return;
				}

				taskQueue.runNextTask( index + 1, $.when( taskQueue.queue[ index ]() ) );
			}

			if ( this.state !== TaskQueue.State.RUNNING ) {
				return;
			}

			currentTask.then( handleThen, handleThen );
		}

		/**
		 * Cancel the queue. No more tasks will be executed.
		 */
		cancel() {
			this.state = TaskQueue.State.CANCELLED;
			this.queue = []; // just to be sure there are no memory leaks
			this.deferred.reject();
		}
	}

	/**
	 * State of the task queue (running, finished etc)
	 *
	 * @enum {string} TaskQueue.State
	 */
	TaskQueue.State = {
		/** not executed yet, tasks can still be added */
		NOT_STARTED: 'not_started',

		/** some task is being executed */
		RUNNING: 'running',

		/** all tasks finished, queue can be discarded */
		FINISHED: 'finished',

		/** cancel() function has been called, queue can be discarded */
		CANCELLED: 'cancelled'
	};

	module.exports = TaskQueue;
}() );
