/*
 * This file is part of the MediaWiki extension MediaViewer.
 *
 * MediaViewer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * MediaViewer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MediaViewer.  If not, see <http://www.gnu.org/licenses/>.
 */

const { TaskQueue } = require( 'mmv' );

( function () {
	QUnit.module( 'mmv.model.TaskQueue', QUnit.newMwEnvironment() );

	QUnit.test( 'TaskQueue constructor sense check', function ( assert ) {
		const taskQueue = new TaskQueue();

		assert.true( taskQueue instanceof TaskQueue, 'TaskQueue created successfully' );
	} );

	QUnit.test( 'Queue length check', function ( assert ) {
		const taskQueue = new TaskQueue();

		assert.strictEqual( taskQueue.queue.length, 0, 'queue is initially empty' );

		taskQueue.push( function () {} );

		assert.strictEqual( taskQueue.queue.length, 1, 'queue length is incremented on push' );
	} );

	QUnit.test( 'State check', function ( assert ) {
		const taskQueue = new TaskQueue();
		const task = $.Deferred();

		taskQueue.push( () => task );

		assert.strictEqual( taskQueue.state, TaskQueue.State.NOT_STARTED,
			'state is initially NOT_STARTED' );

		const promise = taskQueue.execute().then( function () {
			assert.strictEqual( taskQueue.state, TaskQueue.State.FINISHED,
				'state is FINISHED after execution finished' );
		} );

		assert.strictEqual( taskQueue.state, TaskQueue.State.RUNNING,
			'state is RUNNING after execution started' );

		task.resolve();

		return promise;
	} );

	QUnit.test( 'State check for cancellation', function ( assert ) {
		const taskQueue = new TaskQueue();
		const task = $.Deferred();

		taskQueue.push( () => task );
		taskQueue.execute();
		taskQueue.cancel();

		assert.strictEqual( taskQueue.state, TaskQueue.State.CANCELLED,
			'state is CANCELLED after cancellation' );
	} );

	QUnit.test( 'Test executing empty queue', function ( assert ) {
		const taskQueue = new TaskQueue();

		return taskQueue.execute().done( function () {
			assert.true( true, 'Queue promise resolved' );
		} );
	} );

	QUnit.test( 'Simple execution test', function ( assert ) {
		const taskQueue = new TaskQueue();
		let called = false;

		taskQueue.push( function () {
			called = true;
		} );

		return taskQueue.execute().then( function () {
			assert.strictEqual( called, true, 'Task executed successfully' );
		} );
	} );

	QUnit.test( 'Task execution order test', function ( assert ) {
		const taskQueue = new TaskQueue();
		const order = [];

		taskQueue.push( function () {
			order.push( 1 );
		} );

		taskQueue.push( function () {
			const deferred = $.Deferred();

			order.push( 2 );

			setTimeout( function () {
				deferred.resolve();
			}, 0 );

			return deferred;
		} );

		taskQueue.push( function () {
			order.push( 3 );
		} );

		return taskQueue.execute().then( function () {
			assert.deepEqual( order, [ 1, 2, 3 ], 'Tasks executed in order' );
		} );
	} );

	QUnit.test( 'Double execution test', function ( assert ) {
		const taskQueue = new TaskQueue();
		let called = 0;

		taskQueue.push( function () {
			called++;
		} );

		return taskQueue.execute().then( function () {
			return taskQueue.execute();
		} ).then( function () {
			assert.strictEqual( called, 1, 'Task executed only once' );
		} );
	} );

	QUnit.test( 'Parallel execution test', function ( assert ) {
		const taskQueue = new TaskQueue();
		let called = 0;

		taskQueue.push( function () {
			called++;
		} );

		return $.when(
			taskQueue.execute(),
			taskQueue.execute()
		).then( function () {
			assert.strictEqual( called, 1, 'Task executed only once' );
		} );
	} );

	QUnit.test( 'Test push after execute', function ( assert ) {
		const taskQueue = new TaskQueue();

		taskQueue.execute();

		assert.throws( function () {
			taskQueue.push( function () {} );
		}, 'Exception thrown when trying to push to an already running queue' );
	} );

	QUnit.test( 'Test failed task', function ( assert ) {
		const taskQueue = new TaskQueue();

		taskQueue.push( function () {
			return $.Deferred().reject();
		} );

		return taskQueue.execute().done( function () {
			assert.true( true, 'Queue promise resolved' );
		} );
	} );

	QUnit.test( 'Test that tasks wait for each other', function ( assert ) {
		const taskQueue = new TaskQueue();
		let longRunningTaskFinished = false;
		let seenFinished = false;

		taskQueue.push( function () {
			const deferred = $.Deferred();

			setTimeout( function () {
				longRunningTaskFinished = true;
				deferred.resolve();
			}, 0 );

			return deferred;
		} );

		taskQueue.push( function () {
			seenFinished = longRunningTaskFinished;
		} );

		return taskQueue.execute().then( function () {
			assert.true( seenFinished, 'Task waits for previous task to finish' );
		} );
	} );

	QUnit.test( 'Test cancellation before start', function ( assert ) {
		const taskQueue = new TaskQueue();
		let triggered = false;
		const verificationTask = function () {
			triggered = true;
		};

		taskQueue.push( verificationTask );

		taskQueue.cancel();

		taskQueue.execute()
			.done( function () {
				assert.true( false, 'Queue promise rejected' );
			} )
			.fail( function () {
				assert.true( true, 'Queue promise rejected' );
				assert.strictEqual( triggered, false, 'Task was not triggered' );
			} )
			.always( assert.async() );
	} );

	QUnit.test( 'Test cancellation within callback', function ( assert ) {
		const taskQueue = new TaskQueue();
		let triggered = false;
		const verificationTask = function () {
			triggered = true;
		};

		taskQueue.push( function () {
			taskQueue.cancel();
		} );
		taskQueue.push( verificationTask );

		taskQueue.execute()
			.done( function () {
				assert.true( false, 'Queue promise rejected' );
			} )
			.fail( function () {
				assert.true( true, 'Queue promise rejected' );
				assert.strictEqual( triggered, false, 'Task was not triggered' );
			} )
			.always( assert.async() );
	} );

	QUnit.test( 'Test cancellation from task', function ( assert ) {
		const taskQueue = new TaskQueue();
		let triggered = false;
		const task1 = $.Deferred();
		const verificationTask = function () {
			triggered = true;
		};

		taskQueue.push( function () {
			return task1;
		} );
		taskQueue.push( verificationTask );

		setTimeout( function () {
			taskQueue.cancel();
			task1.resolve();
		}, 0 );

		taskQueue.execute()
			.done( function () {
				assert.true( false, 'Queue promise rejected' );
			} )
			.fail( function () {
				assert.true( true, 'Queue promise rejected' );
				assert.strictEqual( triggered, false, 'Task was not triggered' );
			} )
			.always( assert.async() );
	} );

}() );
